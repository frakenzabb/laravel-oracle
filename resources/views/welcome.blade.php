<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Gudang Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #172b4d;
            --brand: #3157d5;
            --page: #f4f7fc;
        }

        body {
            min-height: 100vh;
            color: var(--ink);
            background: radial-gradient(ellipse at 90% 0%, rgba(92, 123, 238, .12), transparent 32rem), var(--page);
        }

        .app-shell { max-width: 1240px; }
        .brand-mark {
            display: grid;
            width: 46px;
            height: 46px;
            place-items: center;
            border-radius: 15px;
            color: #fff;
            background: linear-gradient(145deg, #5378ee, #304fc2);
            box-shadow: 0 8px 18px rgba(49, 87, 213, .24);
            font-size: 1.25rem;
            font-weight: 800;
        }
        .eyebrow {
            color: var(--brand);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }
        .page-title { letter-spacing: -.04em; font-weight: 800; }
        .surface {
            border: 1px solid rgba(226, 232, 240, .8);
            border-radius: 20px;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 12px 36px rgba(26, 43, 77, .055);
        }
        .summary-card { position: relative; overflow: hidden; min-height: 126px; }
        .summary-card::after {
            position: absolute;
            right: -17px;
            bottom: -38px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: var(--tint);
            content: "";
        }
        .summary-value { font-size: 2rem; font-weight: 800; letter-spacing: -.05em; }
        .summary-icon {
            display: grid;
            width: 42px;
            height: 42px;
            place-items: center;
            border-radius: 13px;
            color: var(--accent);
            background: var(--tint);
            font-size: 1.15rem;
            font-weight: 800;
        }
        .form-control, .form-select { min-height: 46px; border-color: #e1e7f0; }
        .form-control:focus, .form-select:focus {
            border-color: #8ea5f4;
            box-shadow: 0 0 0 .2rem rgba(49, 87, 213, .12);
        }
        .btn-primary { border-color: var(--brand); background: var(--brand); }
        .table thead th {
            padding-top: 1rem;
            padding-bottom: 1rem;
            border-bottom: 0;
            color: #77849a;
            background: #f8faff;
            font-size: .72rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .table tbody td { padding-top: .9rem; padding-bottom: .9rem; }
        .type-badge { min-width: 82px; padding: .5rem .7rem; border-radius: 999px; font-weight: 700; }
        .empty-state { padding: 3rem 1rem !important; }
        .dataTables_wrapper .dt-search input { margin-left: .5rem; }
        .dataTables_wrapper .dt-length select { margin: 0 .35rem; }

        @media (max-width: 575.98px) {
            .table thead th, .table tbody td { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <main class="container app-shell py-4 py-lg-5">
        <header class="d-flex align-items-center justify-content-between gap-3 mb-4 mb-lg-5">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-mark" aria-hidden="true">G</div>
                <div>
                    <p class="eyebrow mb-1">Persediaan &amp; transaksi</p>
                    <span class="fw-bold">Gudang<span class="text-primary">.</span></span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('faktur-pembelian.index') }}" class="btn btn-sm btn-outline-primary">Faktur Pembelian</a>
                <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">
                    Terhubung ke Oracle
                </span>
            </div>
        </header>

        <section class="mb-4">
            <p class="eyebrow mb-2">Ringkasan persediaan</p>
            <h1 class="page-title h2 mb-2">Manajemen Gudang Barang</h1>
            <p class="text-secondary mb-0">Catat barang masuk dan keluar, pantau stok, dan lihat riwayat transaksi gudang.</p>
        </section>

        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-4" role="alert">
                <strong>Berhasil.</strong> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm rounded-4" role="alert">
                <strong>Periksa kembali data yang dimasukkan.</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="row g-3 mb-4" aria-label="Ringkasan gudang">
            @foreach([
                ['label' => 'Jenis barang', 'count' => $ringkasan['jenis_barang'], 'symbol' => 'B', 'accent' => '#3157d5', 'tint' => '#edf1ff'],
                ['label' => 'Total stok tersedia', 'count' => number_format($ringkasan['total_stok']), 'symbol' => 'Σ', 'accent' => '#198754', 'tint' => '#e9f7ef'],
                ['label' => 'Total barang masuk', 'count' => number_format($ringkasan['total_masuk']), 'symbol' => '↓', 'accent' => '#087990', 'tint' => '#e4f7fb'],
                ['label' => 'Total barang keluar', 'count' => number_format($ringkasan['total_keluar']), 'symbol' => '↑', 'accent' => '#d38b08', 'tint' => '#fff5dc'],
            ] as $summary)
                <div class="col-6 col-lg-3">
                    <article class="surface summary-card p-3 p-lg-4" style="--accent: {{ $summary['accent'] }}; --tint: {{ $summary['tint'] }}">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <p class="small text-secondary mb-2">{{ $summary['label'] }}</p>
                                <div class="summary-value">{{ $summary['count'] }}</div>
                            </div>
                            <span class="summary-icon" aria-hidden="true">{{ $summary['symbol'] }}</span>
                        </div>
                    </article>
                </div>
            @endforeach
        </section>

        <section class="surface p-3 p-md-4 mb-4">
            <div class="mb-3">
                <p class="eyebrow mb-1">Transaksi baru</p>
                <h2 class="h5 fw-bold mb-0">Catat barang masuk / keluar</h2>
            </div>
            <form action="{{ route('barang.store') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label for="nama_barang" class="form-label small fw-semibold">Nama barang</label>
                    <input
                        type="text"
                        id="nama_barang"
                        name="nama_barang"
                        class="form-control"
                        placeholder="Contoh: Kabel LAN"
                        maxlength="150"
                        value="{{ old('nama_barang') }}"
                        required
                    >
                </div>
                <div class="col-6 col-md-2">
                    <label for="jenis_transaksi" class="form-label small fw-semibold">Jenis transaksi</label>
                    <select id="jenis_transaksi" name="jenis_transaksi" class="form-select" required>
                        <option value="masuk" @selected(old('jenis_transaksi', 'masuk') === 'masuk')>Barang masuk</option>
                        <option value="keluar" @selected(old('jenis_transaksi') === 'keluar')>Barang keluar</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="jumlah" class="form-label small fw-semibold">Jumlah</label>
                    <input type="number" id="jumlah" name="jumlah" class="form-control" min="1" value="{{ old('jumlah', 1) }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label for="tanggal" class="form-label small fw-semibold">Tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" class="form-control" value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Simpan transaksi</button>
                </div>
                <div class="col-12">
                    <label for="keterangan" class="form-label small fw-semibold">Keterangan <span class="fw-normal text-secondary">(opsional)</span></label>
                    <input type="text" id="keterangan" name="keterangan" class="form-control" maxlength="1000" placeholder="Contoh: pembelian dari pemasok" value="{{ old('keterangan') }}">
                </div>
            </form>
        </section>

        <section class="surface p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <p class="eyebrow mb-1">Persediaan saat ini</p>
                    <h2 class="h5 fw-bold mb-0">Stok per barang</h2>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">{{ $stok->count() }} jenis</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama barang</th>
                            <th>Total masuk</th>
                            <th>Total keluar</th>
                            <th>Stok tersedia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stok as $barang)
                            <tr>
                                <td class="fw-semibold">{{ $barang['nama_barang'] }}</td>
                                <td class="text-success">+{{ number_format($barang['masuk']) }}</td>
                                <td class="text-danger">−{{ number_format($barang['keluar']) }}</td>
                                <td><span class="badge rounded-pill {{ $barang['stok'] < 0 ? 'text-bg-danger' : 'text-bg-success' }}">{{ number_format($barang['stok']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty-state text-center text-secondary">Belum ada persediaan barang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="surface p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <p class="eyebrow mb-1">Riwayat gudang</p>
                    <h2 class="h5 fw-bold mb-0">Daftar transaksi barang</h2>
                </div>
                <span class="badge rounded-pill text-bg-light border px-3 py-2 text-secondary">{{ $data->count() }} transaksi</span>
            </div>
            <div class="table-responsive">
                <table id="mutasiTable" class="table align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Nama barang</th>
                            <th>Jenis transaksi</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                            <tr>
                                <td class="text-secondary">#{{ $row->id }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($row->tanggal)->format('d M Y') }}</td>
                                <td class="fw-semibold">{{ $row->nama_barang }}</td>
                                <td>
                                    <span class="badge type-badge {{ $row->jenis_transaksi === 'masuk' ? 'text-bg-success' : 'text-bg-warning' }}">
                                        {{ $row->jenis_transaksi === 'masuk' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td class="fw-semibold">{{ number_format($row->jumlah) }}</td>
                                <td>{{ $row->keterangan ?: '—' }}</td>
                                <td>
                                    <form action="{{ route('barang.destroy', $row->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus transaksi ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="empty-state text-center text-secondary">
                                    <div class="fs-1 mb-2" aria-hidden="true">□</div>
                                    <div class="fw-semibold text-dark">Belum ada transaksi barang</div>
                                    <div class="small">Catat barang masuk atau keluar melalui formulir di atas.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#mutasiTable').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/id.json' },
                order: [[0, 'desc']],
                pageLength: 10,
                pagingType: 'simple_numbers',
                columnDefs: [{ orderable: false, targets: [6] }]
            });
        });
    </script>
</body>
</html>
