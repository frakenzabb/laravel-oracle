<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $faktur->no_transaksi ?? $faktur->nomor_faktur }} - Faktur Pembelian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { color: #172b4d; background: #f4f7fc; }
        .invoice { max-width: 1000px; border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; }
        .table thead th { color: #64748b; background: #f8faff; font-size: .82rem; }
        @media print {
            body { background: #fff; }
            .invoice { max-width: none; border: 0; box-shadow: none !important; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <main class="container py-4 py-lg-5">
        @if(session('success'))
            <div class="alert alert-success no-print" role="alert">{{ session('success') }}</div>
        @endif
        <article class="invoice shadow-sm mx-auto p-3 p-md-5">
            <header class="d-flex justify-content-between align-items-start gap-3 border-bottom pb-3 mb-4">
                <div>
                    <p class="text-primary small fw-bold text-uppercase mb-1">Pembelian barang</p>
                    <h1 class="h2 fw-bold mb-1">Faktur Pembelian</h1>
                    <p class="text-secondary mb-0">Dokumen transaksi pembelian barang.</p>
                </div>
                <div class="text-end">
                    <div class="small text-secondary">No. Transaksi</div>
                    <div class="fw-bold">{{ $faktur->no_transaksi ?? $faktur->nomor_faktur }}</div>
                </div>
            </header>

            <section class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="small text-secondary">Kode Transaksi</div>
                    <div class="fw-semibold">{{ $faktur->kode_transaksi }}</div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="small text-secondary">Tgl. Transaksi</div>
                    <div class="fw-semibold">{{ \Illuminate\Support\Carbon::parse($faktur->tanggal)->format('d/m/Y') }}</div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="small text-secondary">Rekanan / Supplier</div>
                    <div class="fw-semibold">
                        {{ $faktur->nama_rekanan }}
                        @if(!empty($faktur->kode_rekanan))
                            <span class="badge bg-secondary-subtle text-secondary border ms-1">{{ $faktur->kode_rekanan }}</span>
                        @endif
                    </div>
                    @if(!empty($faktur->alamat_rekanan) || !empty($faktur->no_telp_rekanan))
                        <div class="small text-muted mt-1">
                            @if(!empty($faktur->alamat_rekanan))
                                <span>{{ $faktur->alamat_rekanan }}</span>
                            @endif
                            @if(!empty($faktur->alamat_rekanan) && !empty($faktur->no_telp_rekanan))
                                <span> &bull; </span>
                            @endif
                            @if(!empty($faktur->no_telp_rekanan))
                                <span>{{ $faktur->no_telp_rekanan }}</span>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="small text-secondary">Opr. Insert</div>
                    <div class="fw-semibold">{{ $faktur->operator_insert }}</div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="small text-secondary">Tgl. Insert</div>
                    <div class="fw-semibold">{{ \Illuminate\Support\Carbon::parse($faktur->tanggal_insert)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="col-12">
                    <div class="small text-secondary">Keterangan</div>
                    <div>{{ $faktur->keterangan ?: '—' }}</div>
                </div>
            </section>

            <h2 class="h5 fw-bold mb-3">Detail Barang</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->kode_barang ?: '—' }}</td>
                                <td class="fw-semibold">{{ $item->nama_barang }}</td>
                                <td>{{ $item->satuan }}</td>
                                <td>{{ number_format($item->jumlah) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="small text-secondary mt-3">Barang pada faktur ini telah dicatat sebagai barang masuk ke stok gudang.</p>
            <div class="d-flex flex-wrap gap-2 mt-4 no-print">
                <button type="button" class="btn btn-success" onclick="window.print()">Cetak Faktur</button>
                <a href="{{ route('faktur-pembelian.index') }}" class="btn btn-primary">Buat Faktur Baru</a>
                <a href="{{ route('barang.index') }}" class="btn btn-outline-secondary">Kembali ke Gudang</a>
            </div>
        </article>
    </main>
</body>
</html>
