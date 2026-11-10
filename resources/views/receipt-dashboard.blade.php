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
    <div class="app-layout">
        @include('partials.sidebar', ['activePage' => 'receipt'])
        <main class="container-fluid page-shell app-content px-3 px-lg-4 py-3 py-lg-4">
        <header class="page-banner d-flex justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-icon" aria-hidden="true">▣</span>
                <div>
                    <h1 class="h4 fw-bold mb-1">Penerimaan Barang</h1>
                    <p class="mb-0 small text-secondary">Pilih PO yang tersedia untuk mencatat barang diterima</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">PO aktif: {{ number_format($ringkasan['purchase_orders']) }}</span>
            </div>
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
            <p class="section-subtitle mb-3">Kosongkan tanggal untuk menampilkan semua PO; pilih periode tanggal jika ingin membatasi pencarian.</p>
            <div class="row g-3 align-items-end">
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold mb-1" for="tanggal_awal">Tanggal Awal</label>
                    <input class="form-control" type="date" id="tanggal_awal" name="tanggal_awal" value="{{ $tanggalAwal }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label small fw-semibold mb-1" for="tanggal_akhir">Tanggal Akhir</label>
                    <input class="form-control" type="date" id="tanggal_akhir" name="tanggal_akhir" value="{{ $tanggalAkhir }}">
                </div>
                <div class="col-12 col-lg-auto d-flex flex-wrap gap-2">
                    <button class="btn btn-primary px-3" type="submit"><span aria-hidden="true">⌕</span> Cari</button>
                    <button class="btn btn-outline-secondary" type="button" id="thisMonth">Bulan Ini</button>
                    <button class="btn btn-outline-secondary" type="button" id="today">Hari Ini</button>
                    <a class="btn btn-outline-secondary" href="{{ route('barang.index') }}">Reset</a>
                </div>
            </div>
        </form>

        <section class="surface overflow-hidden mb-4" aria-labelledby="supplierListHeading">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom p-3 p-md-4">
                <div>
                    <h2 class="section-title mb-1" id="supplierListHeading">Rekanan dengan PO Tersedia</h2>
                    <p class="section-subtitle mb-0">Pilih rekanan untuk melihat PO yang masih memiliki barang belum diterima.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">{{ number_format($suppliers->count()) }} rekanan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Rekanan</th>
                            <th>Kode</th>
                            <th class="text-center">PO Tersedia</th>
                            <th class="text-end">Qty Belum Diterima</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="supplier-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($supplier['name'], 0, 1)) }}</span>
                                        <span class="fw-semibold">{{ $supplier['name'] }}</span>
                                    </div>
                                </td>
                                <td>{{ $supplier['code'] }}</td>
                                <td class="text-center"><span class="quantity-pill">{{ number_format($supplier['purchase_orders']) }}</span></td>
                                <td class="text-end fw-semibold">{{ number_format($supplier['qty_remaining'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary view-supplier"
                                        data-supplier-id="{{ $supplier['id'] }}"
                                        data-supplier-name="{{ $supplier['name'] }}"
                                        data-supplier-code="{{ $supplier['code'] }}"
                                        aria-label="Lihat PO dari {{ $supplier['name'] }}"
                                    >Lihat PO <span aria-hidden="true">→</span></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="empty-state text-center">
                                    <div class="fw-semibold text-dark">Belum ada rekanan dengan PO tersedia.</div>
                                    <div class="small mt-1">Coba ubah periode tanggal untuk melihat PO lainnya.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface overflow-hidden">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 border-bottom p-3 p-md-4">
                <div>
                    <h2 class="section-title mb-1">Purchase Order Tersedia</h2>
                    @if($tanggalAwal && $tanggalAkhir)
                        <p class="section-subtitle mb-0">PO dengan sisa barang pada periode {{ \Illuminate\Support\Carbon::parse($tanggalAwal)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($tanggalAkhir)->format('d M Y') }}.</p>
                    @else
                        <p class="section-subtitle mb-0">PO dengan sisa barang dari semua periode.</p>
                    @endif
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
                                    <a
                                        class="btn btn-sm btn-primary"
                                        href="{{ route('penerimaan-barang.input', array_filter(['supplier_id' => $purchaseOrder->id_rekanan, 'po_id' => $purchaseOrder->id, 'tanggal_awal' => $tanggalAwal, 'tanggal_akhir' => $tanggalAkhir], fn ($value) => $value !== '')) }}"
                                    >Input Penerimaan <span aria-hidden="true">→</span></a>
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

        <section class="surface overflow-hidden mt-4" aria-label="Stok barang tersedia saat ini">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom p-3 p-md-4">
                <div>
                    <h2 class="section-title mb-1">Barang dan Stok Tersedia Saat Ini</h2>
                    <p class="section-subtitle mb-0">Stok dihitung dari seluruh transaksi barang masuk dan keluar.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border text-secondary">{{ number_format($availableItems->count()) }} barang</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="availableStockTable">
                    <thead>
                        <tr>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th class="text-end">Stok Tersedia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($availableItems as $item)
                            <tr>
                                <td class="po-number">{{ $item->kode_barang ?: '—' }}</td>
                                <td class="fw-semibold">{{ $item->nama_barang }}</td>
                                <td>{{ $item->unit ?: '—' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $item->available_qty, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="empty-state text-center">Data barang belum tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        </main>
    </div>

    <div class="modal fade" id="supplierModal" tabindex="-1" aria-labelledby="supplierModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header px-4 py-3">
                    <div>
                        <h2 class="modal-title fs-5 fw-bold" id="supplierModalTitle">Purchase Order Rekanan</h2>
                        <p class="small text-secondary mb-0" id="supplierModalSubtitle"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-3 p-md-4">
                    <section class="mb-4" aria-labelledby="supplierOrdersHeading">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <h3 class="h6 fw-bold mb-0" id="supplierOrdersHeading">PO Belum Selesai</h3>
                            <span class="badge rounded-pill text-bg-light border text-secondary"><span id="supplierOrderCount">0</span> PO</span>
                        </div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No. PO</th>
                                        <th>Tanggal</th>
                                        <th class="text-center">Baris Tersisa</th>
                                        <th class="text-end">Qty Belum Diterima</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="supplierOrdersBody"></tbody>
                            </table>
                        </div>
                    </section>

                    <section aria-labelledby="supplierHistoryHeading">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <h3 class="h6 fw-bold mb-1" id="supplierHistoryHeading">Barang yang Sudah Diterima</h3>
                                <p class="small text-secondary mb-0" id="supplierHistoryPeriod"></p>
                            </div>
                            <span class="badge rounded-pill text-bg-light border text-secondary"><span id="supplierHistoryCount">0</span> baris</span>
                        </div>
                        <div id="supplierHistoryMessage" class="alert alert-warning d-none" role="alert"></div>
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal Terima</th>
                                        <th>No. Penerimaan</th>
                                        <th>No. Surat Jalan</th>
                                        <th>No. PO</th>
                                        <th>Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th class="text-end">Qty Diterima</th>
                                        <th>Satuan</th>
                                    </tr>
                                </thead>
                                <tbody id="supplierHistoryBody"></tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const purchaseOrders = @json($purchaseOrderOptions);
        const supplierModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('supplierModal'));
        const supplierOrdersBody = document.getElementById('supplierOrdersBody');
        const supplierHistoryBody = document.getElementById('supplierHistoryBody');
        const supplierHistoryMessage = document.getElementById('supplierHistoryMessage');
        const startDate = document.getElementById('tanggal_awal');
        const endDate = document.getElementById('tanggal_akhir');
        let supplierHistorySequence = 0;

        function formatQty(value) {
            return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 }).format(Number(value || 0));
        }

        function formatDate(value) {
            if (!value) return '—';
            const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
            if (!year || !month || !day) return '—';
            return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
                .format(new Date(year, month - 1, day));
        }

        function appendEmptyRow(body, columns, message) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = columns;
            cell.className = 'empty-state text-center';
            cell.textContent = message;
            row.append(cell);
            body.replaceChildren(row);
        }

        async function openSupplierModal(supplierId, supplierName, supplierCode) {
            document.getElementById('supplierModalTitle').textContent = supplierName;
            document.getElementById('supplierModalSubtitle').textContent = supplierCode || 'Daftar PO dan riwayat barang diterima';
            document.getElementById('supplierHistoryPeriod').textContent = startDate.value && endDate.value
                ? `Riwayat periode ${formatDate(startDate.value)} – ${formatDate(endDate.value)}`
                : 'Riwayat dari semua periode';

            const supplierOrders = purchaseOrders.filter((po) => po.supplier_id === supplierId);
            document.getElementById('supplierOrderCount').textContent = supplierOrders.length;
            supplierOrdersBody.replaceChildren();

            supplierOrders.forEach((order) => {
                const row = document.createElement('tr');
                const number = document.createElement('td');
                number.className = 'po-number';
                number.textContent = order.number || `PO #${order.id}`;
                row.append(number);
                row.append(makeCell(formatDate(order.date)));
                row.append(makeCell(formatQty(order.item_count), 'text-center'));
                row.append(makeCell(formatQty(order.qty_remaining), 'text-end fw-semibold'));

                const actionCell = document.createElement('td');
                actionCell.className = 'text-end';
                const action = document.createElement('a');
                action.className = 'btn btn-sm btn-primary';
                const inputUrl = new URL(@json(route('penerimaan-barang.input')), window.location.origin);
                inputUrl.searchParams.set('supplier_id', supplierId);
                inputUrl.searchParams.set('po_id', order.id);
                if (startDate.value && endDate.value) {
                    inputUrl.searchParams.set('tanggal_awal', startDate.value);
                    inputUrl.searchParams.set('tanggal_akhir', endDate.value);
                }
                action.href = inputUrl.toString();
                action.textContent = 'Input Penerimaan';
                actionCell.append(action);
                row.append(actionCell);
                supplierOrdersBody.append(row);
            });
            if (supplierOrders.length === 0) {
                appendEmptyRow(supplierOrdersBody, 5, 'Tidak ada PO terbuka untuk rekanan ini.');
            }

            supplierHistoryMessage.classList.add('d-none');
            supplierHistoryBody.innerHTML = '<tr><td colspan="8" class="text-center text-secondary py-4"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat riwayat penerimaan...</td></tr>';
            document.getElementById('supplierHistoryCount').textContent = '…';
            supplierModal.show();

            const sequence = ++supplierHistorySequence;
            try {
                const url = new URL(
                    @json(route('suppliers.receipt-history', ['supplierId' => '__SUPPLIER_ID__']))
                        .replace('__SUPPLIER_ID__', encodeURIComponent(supplierId)),
                    window.location.origin
                );
                if (startDate.value && endDate.value) {
                    url.searchParams.set('tanggal_awal', startDate.value);
                    url.searchParams.set('tanggal_akhir', endDate.value);
                }
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) throw new Error('Riwayat penerimaan gagal dimuat.');
                const result = await response.json();
                if (sequence !== supplierHistorySequence) return;

                document.getElementById('supplierHistoryCount').textContent = result.items.length;
                supplierHistoryBody.replaceChildren();
                result.items.forEach((item) => {
                    const row = document.createElement('tr');
                    row.append(makeCell(formatDate(item.tanggal)));
                    row.append(makeCell(item.no_transaksi || '—', 'po-number'));
                    row.append(makeCell(item.no_sj || '—'));
                    row.append(makeCell(item.no_po || '—'));
                    row.append(makeCell(item.kode_barang || '—'));
                    row.append(makeCell(item.nama_barang || 'Barang tidak diketahui', 'fw-semibold'));
                    row.append(makeCell(formatQty(item.received_qty), 'text-end fw-semibold'));
                    row.append(makeCell(item.unit || '—'));
                    supplierHistoryBody.append(row);
                });
                if (result.items.length === 0) {
                    appendEmptyRow(supplierHistoryBody, 8, 'Belum ada barang yang diterima dari rekanan ini.');
                }
            } catch (error) {
                if (sequence !== supplierHistorySequence) return;
                document.getElementById('supplierHistoryCount').textContent = '0';
                appendEmptyRow(supplierHistoryBody, 8, 'Riwayat penerimaan tidak tersedia.');
                supplierHistoryMessage.textContent = error.message || 'Terjadi kesalahan saat memuat riwayat penerimaan.';
                supplierHistoryMessage.classList.remove('d-none');
            }
        }

        function makeCell(text, className = '') {
            const cell = document.createElement('td');
            cell.textContent = text;
            if (className) cell.className = className;
            return cell;
        }

        document.querySelectorAll('.view-supplier').forEach((button) => {
            button.addEventListener('click', () => {
                openSupplierModal(
                    button.dataset.supplierId,
                    button.dataset.supplierName,
                    button.dataset.supplierCode
                );
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

    </script>
</body>
</html>
