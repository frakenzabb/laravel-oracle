<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penerimaan Barang dari PO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #1769e8;
            --brand-dark: #0d53c6;
            --page: #f3f6fb;
            --ink: #172b4d;
            --muted: #718096;
            --line: #e7ecf3;
        }

        body { min-height: 100vh; color: var(--ink); background: var(--page); }
        .page-shell { max-width: 1540px; }
        .page-banner {
            color: var(--ink);
            border-radius: 16px;
            border: 1px solid rgba(226, 232, 240, .9);
            background: #fff;
            box-shadow: 0 7px 22px rgba(27, 44, 71, .06);
            transition: box-shadow .2s ease, transform .2s ease;
        }
        .page-banner:hover { box-shadow: 0 12px 28px rgba(27, 44, 71, .1); }
        .brand-icon {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            color: var(--brand);
            background: #eaf2ff;
            font-size: 1.45rem;
        }
        .surface {
            border: 1px solid rgba(226, 232, 240, .9);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 7px 22px rgba(27, 44, 71, .045);
        }
        .stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .stat-card {
            display: flex;
            align-items: flex-start;
            min-height: 126px;
            position: relative;
            overflow: hidden;
            padding: 1.25rem;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            border-color: color-mix(in srgb, var(--accent) 25%, #e2e8f0);
            box-shadow: 0 14px 30px rgba(27, 44, 71, .1);
        }
        .stat-card::after {
            position: absolute;
            top: -34px;
            right: -34px;
            width: 116px;
            height: 116px;
            border-radius: 50%;
            background: var(--tint);
            content: "";
            transition: transform .25s ease;
        }
        .stat-card:hover::after { transform: scale(1.2); }
        .stat-content {
            position: relative;
            z-index: 1;
            width: 100%;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }
        .stat-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            color: var(--accent);
            background: var(--tint);
            font-size: 1.15rem;
            font-weight: 700;
        }
        .stat-label { color: var(--muted); font-size: .77rem; letter-spacing: .06em; text-transform: uppercase; }
        .stat-value { color: var(--accent); font-size: 1.8rem; font-weight: 750; line-height: 1.1; }
        .date-filter { border-radius: 15px; }
        .section-title { font-size: 1.08rem; font-weight: 700; }
        .section-subtitle { color: var(--muted); font-size: .88rem; }
        .search-box { max-width: 340px; }
        .table thead th {
            padding: .9rem 1rem;
            color: #6b7890;
            background: #f8faff;
            border-bottom: 1px solid var(--line);
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .055em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .table tbody td { padding: .95rem 1rem; vertical-align: middle; }
        .table tbody tr { transition: background-color .16s ease; }
        .table-hover > tbody > tr:hover > * { --bs-table-bg-state: #f3f7ff; }
        .po-number { color: var(--brand); font-family: Consolas, monospace; font-weight: 700; }
        .supplier-avatar {
            width: 36px;
            height: 36px;
            display: inline-grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 11px;
            color: #3357ac;
            background: #edf2ff;
            font-weight: 750;
        }
        .quantity-pill {
            display: inline-block;
            min-width: 34px;
            padding: .3rem .55rem;
            border: 1px solid #e3e8ef;
            border-radius: 9px;
            background: #f8fafc;
            color: #344256;
            text-align: center;
            font-weight: 700;
        }
        .btn { transition: transform .16s ease, box-shadow .16s ease, background-color .16s ease; }
        .btn:not(:disabled):hover { transform: translateY(-1px); box-shadow: 0 5px 12px rgba(23, 105, 232, .14); }
        .btn:focus-visible, .form-control:focus-visible, .form-select:focus-visible {
            outline: 3px solid rgba(23, 105, 232, .28);
            outline-offset: 2px;
        }
        .form-control, .form-select { min-height: 43px; border-color: #dce3ed; }
        .form-control:focus, .form-select:focus {
            border-color: #8ab3ff;
            box-shadow: 0 0 0 .2rem rgba(23, 105, 232, .12);
        }
        .detail-table thead th { padding: .7rem .65rem; }
        .detail-table tbody td { padding: .65rem; }
        .empty-state { padding: 3.2rem 1rem !important; color: var(--muted); }
        .modal-content { border: 0; border-radius: 18px; box-shadow: 0 22px 65px rgba(15, 23, 42, .2); }
        .modal-header, .modal-footer { border-color: var(--line); }
        .alert { border-radius: 12px; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
            }
        }

        @media (max-width: 767.98px) {
            .page-banner { align-items: flex-start !important; }
            .stat-grid { grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); }
            .search-box { max-width: none; width: 100%; }
            .table tbody td { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <main class="container-fluid page-shell px-3 px-lg-4 py-3 py-lg-4">
        <header class="page-banner d-flex justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-icon" aria-hidden="true">▣</span>
                <div>
                    <h1 class="h4 fw-bold mb-1">Penerimaan Barang</h1>
                    <p class="mb-0 small text-secondary">Pilih PO yang tersedia untuk mencatat barang diterima</p>
                </div>
            </div>
            <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">PO aktif: {{ number_format($ringkasan['purchase_orders']) }}</span>
        </header>

        @if($dbError)
            <div class="alert alert-warning border-0 shadow-sm" role="alert">
                <strong>Data Purchase Order belum dapat dimuat.</strong>
                <div class="small mt-1">{{ $dbError }}</div>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm" role="alert">
                <strong>Penerimaan belum dapat disimpan. Periksa kembali data berikut:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan PO tersedia">
            <div class="col-12">
                <div class="stat-grid">
                    @foreach([
                        ['label' => 'PO Belum Selesai', 'value' => number_format($ringkasan['purchase_orders']), 'icon' => '▤', 'accent' => '#1769e8', 'tint' => '#eaf2ff'],
                        ['label' => 'Baris Barang Tersisa', 'value' => number_format($ringkasan['items']), 'icon' => '▦', 'accent' => '#07917d', 'tint' => '#e5f7f3'],
                        ['label' => 'Qty Belum Diterima', 'value' => number_format($ringkasan['qty'], 0, ',', '.'), 'icon' => '↙', 'accent' => '#bd7b0a', 'tint' => '#fff5df'],
                        ['label' => 'Jumlah Transaksi', 'value' => number_format($ringkasan['transactions']), 'icon' => '▣', 'accent' => '#6854c7', 'tint' => '#f0edff'],
                        ['label' => 'Total Baris Item', 'value' => number_format($ringkasan['received_items']), 'icon' => '☷', 'accent' => '#0c8b9b', 'tint' => '#e6f7f9'],
                        ['label' => 'Total Qty Diterima', 'value' => number_format($ringkasan['received_qty'], 0, ',', '.'), 'icon' => '✓', 'accent' => '#198754', 'tint' => '#e8f6ee'],
                    ] as $stat)
                        <article class="surface stat-card" style="--accent: {{ $stat['accent'] }}; --tint: {{ $stat['tint'] }}">
                            <div class="stat-content">
                                <div>
                                    <div class="stat-label mb-2">{{ $stat['label'] }}</div>
                                    <div class="stat-value">{{ $stat['value'] }}</div>
                                </div>
                                <span class="stat-icon" aria-hidden="true">{{ $stat['icon'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <form action="{{ route('barang.index') }}" method="GET" class="surface date-filter p-3 p-md-4 mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold mb-1" for="tanggal_awal">Tanggal Awal</label>
                    <input class="form-control" type="date" id="tanggal_awal" name="tanggal_awal" value="{{ $tanggalAwal }}" required>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold mb-1" for="tanggal_akhir">Tanggal Akhir</label>
                    <input class="form-control" type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}" required>
                </div>
                <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
                    <button class="btn btn-primary px-3" type="submit"><span aria-hidden="true">⌕</span> Cari</button>
                    <button class="btn btn-outline-secondary" type="button" id="thisMonth">Bulan Ini</button>
                    <button class="btn btn-outline-secondary" type="button" id="today">Hari Ini</button>
                    <a class="btn btn-outline-secondary" href="{{ route('barang.index') }}">Reset</a>
                </div>
            </div>
        </form>

        <section class="surface overflow-hidden">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom p-3 p-md-4">
                <div>
                    <h2 class="section-title mb-1">Purchase Order Tersedia</h2>
                    <p class="section-subtitle mb-0">PO dengan sisa barang pada periode {{ \Illuminate\Support\Carbon::parse($tanggalAwal)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($tanggalAkhir)->format('d M Y') }}.</p>
                </div>
                <div class="search-box">
                    <input type="search" id="poSearch" class="form-control" placeholder="Cari nomor PO atau rekanan..." aria-label="Cari Purchase Order">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="purchaseOrderTable">
                    <thead>
                        <tr>
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            <th>Rekanan</th>
                            <th class="text-center">Baris Tersisa</th>
                            <th class="text-end">Qty Belum Diterima</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $purchaseOrder)
                            <tr class="po-row">
                                <td class="po-number">{{ $purchaseOrder->no_transaksi ?: 'PO #'.$purchaseOrder->id }}</td>
                                <td>{{ $purchaseOrder->tanggal ? \Illuminate\Support\Carbon::parse($purchaseOrder->tanggal)->format('d M Y') : '—' }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="supplier-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($purchaseOrder->nama_rekanan, 0, 1)) }}</span>
                                        <div>
                                            <div class="fw-semibold">{{ $purchaseOrder->nama_rekanan }}</div>
                                            <div class="small text-secondary">{{ $purchaseOrder->kode_rekanan }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center"><span class="quantity-pill">{{ number_format($purchaseOrder->item_count) }}</span></td>
                                <td class="text-end fw-semibold">{{ number_format($purchaseOrder->qty_remaining, 0, ',', '.') }}</td>
                                <td>
                                    @if($purchaseOrder->received_lines > 0)
                                        <span class="badge rounded-pill text-bg-warning-subtle border border-warning-subtle text-warning-emphasis">Penerimaan sebagian</span>
                                    @else
                                        <span class="badge rounded-pill text-bg-primary-subtle border border-primary-subtle text-primary-emphasis">Belum diterima</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary receive-po"
                                        data-po-id="{{ $purchaseOrder->id }}"
                                        data-supplier-id="{{ $purchaseOrder->id_rekanan }}"
                                    >Pilih PO <span aria-hidden="true">→</span></button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="empty-state text-center">
                                <div class="display-6 mb-2" aria-hidden="true">✓</div>
                                <div class="fw-semibold text-dark">Tidak ada PO dengan barang yang belum diterima.</div>
                                <div class="small mt-1">PO yang sudah seluruhnya diterima tidak ditampilkan.</div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div id="noSearchResults" class="empty-state text-center d-none">Tidak ada PO yang cocok dengan pencarian.</div>
            </div>
        </section>
    </main>

    <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form action="{{ route('penerimaan-barang.store') }}" method="POST" id="receiptForm">
                    @csrf
                    <div class="modal-header px-4 py-3">
                        <div>
                            <h2 class="modal-title fs-5 fw-bold" id="receiptModalTitle">Input Penerimaan Barang</h2>
                            <p class="small text-secondary mb-0">Detail barang dimuat dari PO. Tidak ada barang yang dapat ditambahkan manual.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body p-3 p-md-4">
                        <div id="formMessage" class="alert alert-danger d-none" role="alert"></div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold" for="tanggal">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input id="tanggal" name="tanggal" type="date" class="form-control" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold" for="no_sj">No. Surat Jalan</label>
                                <input id="no_sj" name="no_sj" class="form-control" maxlength="50" value="{{ old('no_sj', '-') }}" placeholder="Nomor surat jalan (opsional)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold" for="tgl_sj">Tanggal Surat Jalan <span class="text-danger">*</span></label>
                                <input id="tgl_sj" name="tgl_sj" type="date" class="form-control" value="{{ old('tgl_sj', now()->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold" for="supplierSelect">Rekanan <span class="text-danger">*</span></label>
                                <select id="supplierSelect" class="form-select" required>
                                    <option value="">-- Pilih Rekanan --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold" for="purchaseOrderSelect">No. PO <span class="text-danger">*</span></label>
                                <select id="purchaseOrderSelect" name="po_id" class="form-select" required disabled>
                                    <option value="">-- Pilih rekanan terlebih dahulu --</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold" for="keterangan">Keterangan <span class="text-secondary fw-normal">(opsional)</span></label>
                                <input id="keterangan" name="keterangan" class="form-control" maxlength="255" value="{{ old('keterangan') }}" placeholder="Catatan penerimaan barang">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <h3 class="h6 fw-bold mb-1">Detail Barang PO</h3>
                                <p class="small text-secondary mb-0">Qty terima otomatis mengikuti sisa PO; ubah untuk penerimaan sebagian atau hapus baris yang tidak diterima.</p>
                            </div>
                            <span class="badge rounded-pill text-bg-light border text-secondary text-nowrap"><span id="itemCount">0</span> baris</span>
                        </div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover detail-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 55px">No</th>
                                        <th>Kode</th>
                                        <th>Nama Barang</th>
                                        <th class="text-end">Qty PO</th>
                                        <th style="width: 155px">Qty Terima</th>
                                        <th>Satuan</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="poItemsBody">
                                    <tr><td colspan="7" class="text-center text-secondary py-5">Pilih rekanan lalu No. PO untuk memuat detail barang.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between px-3 px-md-4">
                        <span class="small text-secondary d-none d-md-inline">Barang yang tidak diterima dapat dihapus menggunakan tombol hapus.</span>
                        <div class="d-flex gap-2 ms-auto">
                            <button type="reset" class="btn btn-outline-secondary" id="resetForm">Reset</button>
                            <button type="submit" class="btn btn-primary px-4" id="saveReceipt" disabled>Simpan Penerimaan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const purchaseOrders = @json($purchaseOrderOptions);
        const oldReceiptItems = @json(old('items', []));
        const supplierSelect = document.getElementById('supplierSelect');
        const purchaseOrderSelect = document.getElementById('purchaseOrderSelect');
        const poItemsBody = document.getElementById('poItemsBody');
        const itemCount = document.getElementById('itemCount');
        const saveReceipt = document.getElementById('saveReceipt');
        const formMessage = document.getElementById('formMessage');
        const startDate = document.getElementById('tanggal_awal');
        const endDate = document.getElementById('tanggal_akhir');
        let lineIndex = 0;
        let loadSequence = 0;
        let restoreOldItems = true;

        function showFormMessage(message) {
            formMessage.textContent = message;
            formMessage.classList.remove('d-none');
        }

        function hideFormMessage() {
            formMessage.textContent = '';
            formMessage.classList.add('d-none');
        }

        function formatQty(value) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value || 0));
        }

        function updateLinesState() {
            const rows = [...poItemsBody.querySelectorAll('.po-item-row')];
            rows.forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
            });
            itemCount.textContent = rows.length;
            saveReceipt.disabled = rows.length === 0;
        }

        function makeCell(text, className = '') {
            const cell = document.createElement('td');
            cell.textContent = text;
            if (className) cell.className = className;
            return cell;
        }

        function renderLines(lines) {
            poItemsBody.replaceChildren();
            lineIndex = 0;
            lines.forEach((line) => {
                const row = document.createElement('tr');
                row.className = 'po-item-row';
                row.dataset.remaining = line.remaining_qty;
                row.append(makeCell('', 'row-number text-secondary'));
                row.append(makeCell(line.kode_barang || '—', 'small text-secondary'));

                const nameCell = makeCell(line.nama_barang || 'Barang tanpa nama');
                nameCell.classList.add('fw-semibold');
                row.append(nameCell);
                row.append(makeCell(`${formatQty(line.ordered_qty)} (sisa ${formatQty(line.remaining_qty)})`, 'text-end text-nowrap'));

                const qtyCell = document.createElement('td');
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.name = `items[${lineIndex}][qty]`;
                qtyInput.className = 'form-control form-control-sm receive-qty';
                qtyInput.min = '0';
                qtyInput.max = line.remaining_qty;
                qtyInput.step = 'any';
                const oldItem = restoreOldItems
                    ? oldReceiptItems.find((item) => String(item.po_detail_id) === String(line.po_detail_id))
                    : null;
                qtyInput.value = oldItem?.qty ?? line.remaining_qty;
                qtyInput.required = true;
                qtyInput.setAttribute('aria-label', `Jumlah diterima untuk ${line.nama_barang || 'barang'}`);
                qtyCell.append(qtyInput);

                const detailInput = document.createElement('input');
                detailInput.type = 'hidden';
                detailInput.name = `items[${lineIndex}][po_detail_id]`;
                detailInput.value = line.po_detail_id;
                qtyCell.append(detailInput);
                row.append(qtyCell);
                row.append(makeCell(line.unit || '—'));

                const actionCell = document.createElement('td');
                actionCell.className = 'text-end';
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-sm btn-outline-danger remove-line';
                removeButton.textContent = 'Hapus';
                removeButton.setAttribute('aria-label', `Hapus ${line.nama_barang || 'barang'} dari penerimaan`);
                actionCell.append(removeButton);
                row.append(actionCell);
                poItemsBody.append(row);
                lineIndex += 1;
            });

            if (lines.length === 0) {
                poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Tidak ada barang yang tersisa untuk diterima pada PO ini.</td></tr>';
            }
            restoreOldItems = false;
            updateLinesState();
        }

        async function loadPurchaseOrder(id) {
            const sequence = ++loadSequence;
            hideFormMessage();
            saveReceipt.disabled = true;
            poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat detail PO...</td></tr>';
            itemCount.textContent = '…';

            try {
                const url = @json(route('purchase-orders.details', ['id' => '__PO_ID__'])).replace('__PO_ID__', encodeURIComponent(id));
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error(response.status === 404 ? 'PO tidak ditemukan atau sudah tidak memiliki sisa barang.' : 'Detail PO gagal dimuat.');
                const purchaseOrder = await response.json();
                if (sequence !== loadSequence) return;
                renderLines(purchaseOrder.items);
            } catch (error) {
                if (sequence !== loadSequence) return;
                poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Detail barang tidak tersedia.</td></tr>';
                itemCount.textContent = '0';
                showFormMessage(error.message || 'Terjadi kesalahan saat memuat detail PO.');
            }
        }

        function updatePurchaseOrderOptions(selectedId = '') {
            const supplierId = supplierSelect.value;
            const supplierOrders = purchaseOrders.filter((po) => po.supplier_id === supplierId);
            purchaseOrderSelect.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = supplierId ? '-- Pilih No. PO --' : '-- Pilih rekanan terlebih dahulu --';
            purchaseOrderSelect.append(placeholder);

            supplierOrders.forEach((po) => {
                const option = document.createElement('option');
                option.value = po.id;
                option.textContent = po.number || `PO #${po.id}`;
                option.selected = po.id === String(selectedId);
                purchaseOrderSelect.append(option);
            });
            purchaseOrderSelect.disabled = supplierOrders.length === 0;
            if (selectedId && supplierOrders.some((po) => po.id === String(selectedId))) {
                purchaseOrderSelect.value = String(selectedId);
            }
        }

        supplierSelect.addEventListener('change', () => {
            updatePurchaseOrderOptions();
            loadSequence += 1;
            hideFormMessage();
            poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Pilih No. PO untuk memuat detail barang.</td></tr>';
            itemCount.textContent = '0';
            saveReceipt.disabled = true;
        });

        purchaseOrderSelect.addEventListener('change', () => {
            if (purchaseOrderSelect.value) loadPurchaseOrder(purchaseOrderSelect.value);
            else {
                loadSequence += 1;
                poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Pilih No. PO untuk memuat detail barang.</td></tr>';
                updateLinesState();
            }
        });

        document.getElementById('receiptForm').addEventListener('reset', () => {
            window.setTimeout(() => {
                loadSequence += 1;
                supplierSelect.value = '';
                updatePurchaseOrderOptions();
                poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Pilih rekanan lalu No. PO untuk memuat detail barang.</td></tr>';
                itemCount.textContent = '0';
                saveReceipt.disabled = true;
                hideFormMessage();
            });
        });

        poItemsBody.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-line');
            if (!button) return;
            button.closest('.po-item-row').remove();
            updateLinesState();
        });

        document.querySelectorAll('.receive-po').forEach((button) => {
            button.addEventListener('click', () => {
                supplierSelect.value = button.dataset.supplierId;
                updatePurchaseOrderOptions(button.dataset.poId);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();
                loadPurchaseOrder(button.dataset.poId);
            });
        });

        document.getElementById('poSearch').addEventListener('input', (event) => {
            const search = event.target.value.trim().toLocaleLowerCase('id');
            let visible = 0;
            document.querySelectorAll('.po-row').forEach((row) => {
                const matches = row.textContent.toLocaleLowerCase('id').includes(search);
                row.classList.toggle('d-none', !matches);
                if (matches) visible += 1;
            });
            document.getElementById('noSearchResults').classList.toggle('d-none', visible > 0 || search === '');
        });

        document.getElementById('thisMonth').addEventListener('click', () => {
            const now = new Date();
            startDate.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
            endDate.value = now.toISOString().slice(0, 10);
            startDate.form.requestSubmit();
        });

        document.getElementById('today').addEventListener('click', () => {
            const today = new Date().toISOString().slice(0, 10);
            startDate.value = today;
            endDate.value = today;
            startDate.form.requestSubmit();
        });

        document.getElementById('receiptForm').addEventListener('submit', (event) => {
            const invalidQty = [...poItemsBody.querySelectorAll('.receive-qty')].some((input) => {
                const value = Number(input.value);
                return !Number.isFinite(value) || value < 0 || value > Number(input.max);
            });
            const hasReceivedQty = [...poItemsBody.querySelectorAll('.receive-qty')].some((input) => Number(input.value) > 0);

            if (!purchaseOrderSelect.value || invalidQty || !hasReceivedQty) {
                event.preventDefault();
                showFormMessage(!purchaseOrderSelect.value
                    ? 'Pilih No. PO terlebih dahulu.'
                    : invalidQty
                        ? 'Jumlah terima harus valid dan tidak boleh melebihi sisa PO.'
                        : 'Isi jumlah terima lebih dari 0 pada minimal satu barang.');
            }
        });

        @if($errors->any() && old('po_id'))
            const previousPurchaseOrder = purchaseOrders.find((po) => po.id === '{{ old('po_id') }}');
            if (previousPurchaseOrder) {
                supplierSelect.value = previousPurchaseOrder.supplier_id;
                updatePurchaseOrderOptions(previousPurchaseOrder.id);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();
                loadPurchaseOrder(previousPurchaseOrder.id);
            }
        @endif
    </script>
</body>
</html>
