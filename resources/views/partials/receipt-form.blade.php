<form action="{{ route('penerimaan-barang.store') }}" method="POST" id="receiptForm">
    @csrf
    <input type="hidden" name="tanggal_awal" value="{{ $tanggalAwal }}">
    <input type="hidden" name="tanggal_akhir" value="{{ $tanggalAkhir }}">
    <input type="hidden" name="return_to_input" value="1">

    <div class="p-3 p-md-4 border-bottom">
        <h2 class="h5 fw-bold mb-1">Informasi Penerimaan</h2>
        <p class="small text-secondary mb-0">Detail barang dimuat dari PO. Tidak ada barang yang dapat ditambahkan manual.</p>
    </div>
    <div class="p-3 p-md-4">
        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Penerimaan belum dapat disimpan. Periksa kembali data berikut:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
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
                <select id="supplierSelect" name="supplier_id" class="form-select" required>
                    <option value="">-- Pilih Rekanan --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier['id'] }}" @selected($initialSupplierId === $supplier['id'])>{{ $supplier['name'] }}</option>
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
    <div class="p-3 p-md-4 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="small text-secondary">Barang yang tidak diterima dapat dihapus menggunakan tombol hapus.</span>
        <div class="d-flex gap-2 ms-auto">
            <button type="reset" class="btn btn-outline-secondary" id="resetForm">Reset</button>
            <button type="submit" class="btn btn-primary px-4" id="saveReceipt" disabled>Simpan Penerimaan</button>
        </div>
    </div>
</form>
