<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon Keluar Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; color: #172b4d; background: #f3f6fb; }
        .page-shell { max-width: 1540px; }
        .surface { border: 1px solid #e7ecf3; border-radius: 16px; background: #fff; box-shadow: 0 7px 22px rgba(27, 44, 71, .05); }
        .brand-icon { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; color: #1769e8; background: #eaf2ff; font-size: 1.4rem; }
        .form-control, .form-select { min-height: 43px; border-color: #dce3ed; }
        .table thead th { color: #6b7890; background: #f8faff; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; }
        .stock-label { font-size: .82rem; color: #718096; }
        .guide summary { color: #1769e8; font-weight: 700; cursor: pointer; }
        .guide summary:focus-visible { outline: 3px solid rgba(23, 105, 232, .28); outline-offset: 3px; border-radius: 3px; }
        .guide-list li + li { margin-top: .5rem; }
        @media (max-width: 767.98px) { .table td { min-width: 130px; } }
    </style>
</head>
<body>
    <div class="app-layout">
        @include('partials.sidebar', ['activePage' => 'outgoing'])
        <main class="container-fluid page-shell app-content px-3 px-lg-4 py-3 py-lg-4">
        <header class="surface d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4">
            <div class="d-flex align-items-center gap-3">
                <span class="brand-icon" aria-hidden="true">↗</span>
                <div>
                    <h1 class="h4 fw-bold mb-1">Barang Keluar</h1>
                    <p class="small text-secondary mb-0">Catat pemakaian barang gudang tanpa Purchase Order.</p>
                </div>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm" role="alert">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm" role="alert">
                <strong>Bon Keluar belum dapat disimpan.</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('bon-keluar.store') }}" method="POST" class="surface p-3 p-md-4" id="bonForm">
            @csrf
            <h2 class="h5 fw-bold mb-3">Informasi Transaksi</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="tanggal">Tanggal <span class="text-danger">*</span></label>
                    <input class="form-control" id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold" for="divisi">Divisi <span class="text-danger">*</span></label>
                    <input class="form-control" id="divisi" name="divisi" value="{{ old('divisi') }}" maxlength="150" placeholder="Contoh: Engineering" required>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold" for="keperluan">Keperluan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="keperluan" name="keperluan" rows="2" maxlength="1000" placeholder="Contoh: Perbaikan mesin produksi" required>{{ old('keperluan') }}</textarea>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 fw-bold mb-1">Daftar Barang Keluar</h2>
                    <p class="small text-secondary mb-0">Jumlah tidak boleh melebihi stok tersedia.</p>
                </div>
                <button class="btn btn-outline-primary" type="button" id="addItem">+ Tambah Barang</button>
            </div>
            <div class="table-responsive border rounded-3 mb-4">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 55px">No</th>
                            <th>Barang</th>
                            <th style="width: 170px">Jumlah</th>
                            <th style="width: 140px">Satuan</th>
                            <th style="width: 100px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <a class="btn btn-link text-decoration-none px-0" href="{{ route('barang.index') }}">← Kembali</a>
                <button class="btn btn-primary px-4" type="submit" id="saveBon">Simpan Bon Keluar</button>
            </div>
        </form>

        <section class="surface overflow-hidden mt-4" aria-label="Riwayat barang keluar">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 border-bottom p-3 p-md-4">
                <div>
                    <h2 class="h5 fw-bold mb-1">Riwayat Barang Keluar</h2>
                    <p class="small text-secondary mb-0">Maksimal 100 detail transaksi terbaru.</p>
                </div>
                <span class="badge rounded-pill text-bg-light border text-secondary">{{ number_format($outgoingItems->count()) }} baris</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-3">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Bon</th>
                            <th>Barang</th>
                            <th>Keterangan</th>
                            <th>Satuan</th>
                            <th class="text-end">Jumlah Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($outgoingItems as $outgoingItem)
                            <tr>
                                <td class="text-nowrap">{{ $outgoingItem->tgl_transaksi ? \Illuminate\Support\Carbon::parse($outgoingItem->tgl_transaksi)->format('d M Y') : '—' }}</td>
                                <td class="text-nowrap fw-semibold text-primary">{{ $outgoingItem->no_transaksi ?: 'Bon #'.$outgoingItem->id_transaksi }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $outgoingItem->nama_barang ?: 'Barang #'.$outgoingItem->id_barang }}</div>
                                    @if($outgoingItem->kode_barang)
                                        <div class="small text-secondary">{{ $outgoingItem->kode_barang }}</div>
                                    @endif
                                </td>
                                <td class="text-secondary">{{ $outgoingItem->transaction_description ?: '—' }}</td>
                                <td>{{ $outgoingItem->unit ?: '—' }}</td>
                                <td class="text-end fw-semibold">{{ number_format((float) $outgoingItem->jumlah, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-secondary py-5">Belum ada transaksi barang keluar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        </main>
    </div>

    <script>
        const products = @json($items);
        const oldItems = @json(old('items', []));
        const itemsBody = document.getElementById('itemsBody');
        let lineIndex = 0;

        function renumberRows() {
            [...itemsBody.querySelectorAll('tr')].forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
            });
        }

        function addRow(previous = {}) {
            const index = lineIndex++;
            const row = document.createElement('tr');
            const options = products.map((product) => {
                const label = `${product.code ? product.code + ' - ' : ''}${product.name}`;
                return `<option value="${product.id}" data-unit="${escapeAttribute(product.unit || '—')}" data-stock="${product.stock}" ${String(product.id) === String(previous.id_barang || '') ? 'selected' : ''}>${escapeHtml(label)}</option>`;
            }).join('');

            row.innerHTML = `
                <td class="row-number text-secondary"></td>
                <td>
                    <select class="form-select item-select" name="items[${index}][id_barang]" required>
                        <option value="">-- Pilih barang --</option>${options}
                    </select>
                    <div class="stock-label mt-1"></div>
                </td>
                <td><input class="form-control qty-input" name="items[${index}][qty]" type="number" min="1" step="1" value="${escapeAttribute(previous.qty || '')}" required></td>
                <td class="unit-label text-secondary">—</td>
                <td><button class="btn btn-sm btn-outline-danger remove-row" type="button">Hapus</button></td>`;
            itemsBody.append(row);
            const select = row.querySelector('.item-select');
            select.addEventListener('change', () => updateRow(row));
            row.querySelector('.remove-row').addEventListener('click', () => {
                row.remove();
                renumberRows();
            });
            updateRow(row);
            renumberRows();
        }

        function updateRow(row) {
            const select = row.querySelector('.item-select');
            const option = select.selectedOptions[0];
            const quantity = row.querySelector('.qty-input');
            const unit = row.querySelector('.unit-label');
            const stockLabel = row.querySelector('.stock-label');
            const stock = Number(option?.dataset.stock || 0);
            quantity.max = stock;
            unit.textContent = option?.dataset.unit || '—';
            stockLabel.textContent = select.value ? `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(stock)}` : '';
        }

        function escapeHtml(value) {
            return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
        }

        function escapeAttribute(value) {
            return escapeHtml(value);
        }

        document.getElementById('addItem').addEventListener('click', () => addRow());
        document.getElementById('bonForm').addEventListener('submit', (event) => {
            const rows = [...itemsBody.querySelectorAll('tr')];
            const selected = rows.map((row) => row.querySelector('.item-select').value);
            const invalid = rows.some((row) => {
                const input = row.querySelector('.qty-input');
                return !input.value || Number(input.value) < 1 || Number(input.value) > Number(input.max);
            });

            if (!rows.length || selected.some((id) => !id) || invalid) {
                event.preventDefault();
                window.alert(!rows.length
                    ? 'Tambahkan minimal satu barang.'
                    : 'Pilih barang dan masukkan jumlah yang valid sesuai stok tersedia.');
            }
        });

        if (Array.isArray(oldItems) && oldItems.length) oldItems.forEach((item) => addRow(item));
        else addRow();
    </script>
</body>
</html>
