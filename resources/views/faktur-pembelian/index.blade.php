<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Pembelian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --ink: #172b4d; --brand: #3157d5; --page: #f4f7fc; }
        body { min-height: 100vh; color: var(--ink); background: var(--page); }
        .app-shell { max-width: 1240px; }
        .surface { border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; box-shadow: 0 10px 28px rgba(26, 43, 77, .05); }
        .page-title { letter-spacing: -.04em; font-weight: 800; }
        .section-title { padding: .85rem 1rem; border-bottom: 1px solid #e7edf5; background: #f8faff; font-weight: 700; }
        .form-control, .form-select { min-height: 42px; border-color: #dfe6ef; }
        .btn-primary { border-color: var(--brand); background: var(--brand); }
        .table thead th { color: #64748b; background: #f8faff; font-size: .82rem; }
        .item-row input { min-width: 90px; }
        @media (max-width: 575.98px) {
            .table thead th, .table tbody td { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <main class="container app-shell py-4 py-lg-5">
        <header class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <div>
                <p class="text-primary small fw-bold text-uppercase mb-1">Pembelian barang</p>
                <h1 class="page-title h2 mb-1">Faktur Pembelian</h1>
                <p class="text-secondary mb-0">Input dan kelola data faktur pembelian barang.</p>
            </div>
            <a href="{{ route('barang.index') }}" class="btn btn-outline-secondary">Kembali ke gudang</a>
        </header>

        @if(!empty($dbError))
            <div class="alert alert-warning border-0 shadow-sm rounded-3 p-3 mb-3" role="alert">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fs-5">⚠️</span>
                    <strong>Koneksi Database Oracle Belum Terhubung</strong>
                </div>
                <p class="mb-1 text-danger small font-monospace">{{ $dbError }}</p>
                <div class="small text-secondary mt-2">
                    Silakan buka file <code>.env</code> dan lengkapi <code>DB_USERNAME</code> serta <code>DB_PASSWORD</code> untuk akun database Oracle Anda.
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success" role="alert">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Periksa kembali data yang dimasukkan.</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('faktur-pembelian.store') }}" method="POST">
            @csrf
            <section class="surface overflow-hidden mb-4">
                <div class="section-title">Informasi Transaksi</div>
                <div class="row g-3 p-3 p-md-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="nomor_faktur">No. Transaksi</label>
                        <input id="nomor_faktur" class="form-control bg-light" value="Nomor dibuat otomatis saat disimpan" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="kode_transaksi">Kode Transaksi</label>
                        <select id="kode_transaksi" name="kode_transaksi" class="form-select" required>
                            <option value="FAKTUR" @selected(old('kode_transaksi', 'FAKTUR') === 'FAKTUR')>FAKTUR</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="tanggal">Tgl. Transaksi</label>
                        <input id="tanggal" name="tanggal" type="date" class="form-control" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="operator_insert">Opr. Insert</label>
                        <input id="operator_insert" class="form-control bg-light" value="{{ auth()->user()?->name ?? 'admin' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="tanggal_insert">Tgl. Insert</label>
                        <input id="tanggal_insert" class="form-control bg-light" value="{{ now()->format('d/m/Y H:i') }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="nama_rekanan">Pilih Rekanan <span class="text-danger">*</span></label>
                        <input id="nama_rekanan" name="nama_rekanan" list="rekananList" class="form-control" maxlength="150" placeholder="Pilih atau ketik rekanan" value="{{ old('nama_rekanan') }}" required autocomplete="off">
                        <datalist id="rekananList">
                            @foreach($rekananList ?? [] as $rekanan)
                                <option value="{{ $rekanan->nama_rekanan }}">{{ $rekanan->kode_rekanan }} — {{ $rekanan->nama_rekanan }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="keterangan">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" class="form-control" rows="2" maxlength="1000" placeholder="Keterangan pembelian (opsional)">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="surface overflow-hidden mb-4">
                <div class="section-title">Detail Barang</div>
                <div class="p-3 p-md-4">
                    <p class="small text-secondary mb-3">Pilih atau isi kode barang, nama barang, satuan, dan jumlah. Barang yang disimpan akan otomatis dicatat sebagai barang masuk.</p>
                    
                    <datalist id="itemNamaList">
                        @foreach($itemList ?? [] as $it)
                            <option value="{{ $it->nama_barang }}">{{ $it->kode_barang }} — {{ $it->nama_barang }} ({{ $it->satuan }})</option>
                        @endforeach
                    </datalist>
                    <datalist id="itemKodeList">
                        @foreach($itemList ?? [] as $it)
                            <option value="{{ $it->kode_barang }}">{{ $it->kode_barang }} — {{ $it->nama_barang }} ({{ $it->satuan }})</option>
                        @endforeach
                    </datalist>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-3">
                            <thead>
                                <tr>
                                    <th style="width: 56px">No</th>
                                    <th>Kode Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Satuan</th>
                                    <th style="width: 150px">Jumlah</th>
                                    <th style="width: 88px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody">
                                @php
                                    $formItems = old('items', [['kode_barang' => '', 'nama_barang' => '', 'satuan' => '', 'jumlah' => '']]);
                                @endphp
                                @foreach($formItems as $index => $item)
                                    <tr class="item-row">
                                        <td class="row-number">{{ $loop->iteration }}</td>
                                        <td><input name="items[{{ $index }}][kode_barang]" list="itemKodeList" class="form-control item-kode" maxlength="50" placeholder="Kode barang" value="{{ $item['kode_barang'] ?? '' }}" autocomplete="off"></td>
                                        <td><input name="items[{{ $index }}][nama_barang]" list="itemNamaList" class="form-control item-nama" maxlength="150" placeholder="Nama barang" value="{{ $item['nama_barang'] ?? '' }}" required autocomplete="off"></td>
                                        <td><input name="items[{{ $index }}][satuan]" class="form-control item-satuan" maxlength="30" placeholder="Satuan" value="{{ $item['satuan'] ?? '' }}" required></td>
                                        <td><input name="items[{{ $index }}][jumlah]" type="number" class="form-control" min="1" max="2147483647" placeholder="Jumlah" value="{{ $item['jumlah'] ?? '' }}" required></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" aria-label="Hapus barang">Hapus</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" id="addItem" class="btn btn-outline-primary">+ Tambah Barang Lainnya</button>
                </div>
            </section>

            <div class="d-flex flex-wrap gap-2 mb-5">
                <button type="submit" class="btn btn-primary px-4">Simpan Faktur</button>
                <button type="reset" class="btn btn-outline-secondary px-4">Reset</button>
                <a href="{{ route('barang.index') }}" class="btn btn-outline-secondary px-4">Kembali</a>
            </div>
        </form>

        <section class="surface p-3 p-md-4">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <div>
                    <p class="text-primary small fw-bold text-uppercase mb-1">Riwayat</p>
                    <h2 class="h5 fw-bold mb-0">Faktur Pembelian Terakhir</h2>
                </div>
                <span class="badge rounded-pill text-bg-light border text-secondary">{{ $faktur->count() }} faktur</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal</th>
                            <th>Rekanan</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faktur as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row->no_transaksi ?? $row->nomor_faktur }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                <td>{{ $row->nama_rekanan }}</td>
                                <td>{{ $row->keterangan ?: '—' }}</td>
                                <td><a href="{{ route('faktur-pembelian.show', $row->id_transaksi ?? $row->id) }}" class="btn btn-sm btn-outline-primary">Lihat / Cetak</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada faktur pembelian.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <template id="itemTemplate">
        <tr class="item-row">
            <td class="row-number"></td>
            <td><input name="items[__INDEX__][kode_barang]" list="itemKodeList" class="form-control item-kode" maxlength="50" placeholder="Kode barang" autocomplete="off"></td>
            <td><input name="items[__INDEX__][nama_barang]" list="itemNamaList" class="form-control item-nama" maxlength="150" placeholder="Nama barang" required autocomplete="off"></td>
            <td><input name="items[__INDEX__][satuan]" class="form-control item-satuan" maxlength="30" placeholder="Satuan" required></td>
            <td><input name="items[__INDEX__][jumlah]" type="number" class="form-control" min="1" max="2147483647" placeholder="Jumlah" required></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-item" aria-label="Hapus barang">Hapus</button></td>
        </tr>
    </template>
    <script>
        const masterItems = @json($itemList ?? []);
        const itemsBody = document.getElementById('itemsBody');
        const itemTemplate = document.getElementById('itemTemplate');
        let nextItemIndex = itemsBody.querySelectorAll('.item-row').length;

        function updateItemNumbers() {
            itemsBody.querySelectorAll('.row-number').forEach((cell, index) => {
                cell.textContent = index + 1;
            });
        }

        function syncByKode(row, kode) {
            if (!kode || !masterItems.length) return;
            const match = masterItems.find(it => (it.kode_barang || '').toLowerCase() === kode.trim().toLowerCase());
            if (match) {
                const namaInput = row.querySelector('.item-nama');
                const satuanInput = row.querySelector('.item-satuan');
                if (namaInput && (!namaInput.value || namaInput.value.trim() === '')) {
                    namaInput.value = match.nama_barang || '';
                }
                if (satuanInput && match.satuan) {
                    satuanInput.value = match.satuan;
                }
            }
        }

        function syncByNama(row, nama) {
            if (!nama || !masterItems.length) return;
            const match = masterItems.find(it => (it.nama_barang || '').toLowerCase() === nama.trim().toLowerCase());
            if (match) {
                const kodeInput = row.querySelector('.item-kode');
                const satuanInput = row.querySelector('.item-satuan');
                if (kodeInput && (!kodeInput.value || kodeInput.value.trim() === '')) {
                    kodeInput.value = match.kode_barang || '';
                }
                if (satuanInput && match.satuan) {
                    satuanInput.value = match.satuan;
                }
            }
        }

        itemsBody.addEventListener('change', (event) => {
            const target = event.target;
            const row = target.closest('.item-row');
            if (!row) return;

            if (target.classList.contains('item-kode')) {
                syncByKode(row, target.value);
            } else if (target.classList.contains('item-nama')) {
                syncByNama(row, target.value);
            }
        });

        itemsBody.addEventListener('input', (event) => {
            const target = event.target;
            const row = target.closest('.item-row');
            if (!row) return;

            if (target.classList.contains('item-kode')) {
                syncByKode(row, target.value);
            } else if (target.classList.contains('item-nama')) {
                syncByNama(row, target.value);
            }
        });

        document.getElementById('addItem').addEventListener('click', () => {
            const row = itemTemplate.content.cloneNode(true);
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace('__INDEX__', nextItemIndex);
            });
            nextItemIndex += 1;
            itemsBody.append(row);
            updateItemNumbers();
        });

        itemsBody.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-item');
            if (!button) return;

            const row = button.closest('.item-row');
            if (itemsBody.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach((input) => { input.value = ''; });
            }
            updateItemNumbers();
        });

        document.querySelector('form').addEventListener('reset', () => {
            window.setTimeout(() => {
                itemsBody.querySelectorAll('.item-row').forEach((row, index) => {
                    if (index > 0) row.remove();
                });
                updateItemNumbers();
            });
        });
    </script>
</body>
</html>
