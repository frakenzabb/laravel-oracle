<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang Masuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; color: #172b4d; background: #f3f6fb; }
        .page-shell { max-width: 1540px; }
        .page-heading, .surface { border: 1px solid #e7ecf3; border-radius: 16px; background: #fff; box-shadow: 0 7px 22px rgba(27, 44, 71, .05); }
        .brand-icon { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; color: #1769e8; background: #eaf2ff; font-size: 1.4rem; }
        .form-control, .form-select { min-height: 43px; border-color: #dce3ed; }
        .table thead th { color: #6b7890; background: #f8faff; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; }
        .detail-table thead th { padding: .7rem .65rem; }
        .detail-table tbody td { padding: .65rem; }
        @media (max-width: 767.98px) { .table td { min-width: 110px; } }
    </style>
</head>
<body>
    <div class="app-layout">
        @include('partials.sidebar', ['activePage' => 'input'])
        <main class="container-fluid page-shell app-content px-3 px-lg-4 py-3 py-lg-4">
            <header class="page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 p-md-4 mb-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="brand-icon" aria-hidden="true">＋</span>
                    <div>
                        <h1 class="h4 fw-bold mb-1">Barang Masuk (Input)</h1>
                        <p class="small text-secondary mb-0">Catat penerimaan barang berdasarkan Purchase Order.</p>
                    </div>
                </div>
                <a class="btn btn-outline-secondary" href="{{ route('barang.index') }}">Kembali ke Daftar PO</a>
            </header>

            @if($dbError)
                <div class="alert alert-warning" role="alert">
                    <strong>Data Purchase Order belum dapat dimuat.</strong>
                    <div class="small mt-1">{{ $dbError }}</div>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            <section class="surface overflow-hidden">
                @include('partials.receipt-form')
            </section>
        </main>
    </div>

    <script>
        const purchaseOrders = @json($purchaseOrderOptions);
        const oldReceiptItems = @json(old('items', []));
        const supplierSelect = document.getElementById('supplierSelect');
        const purchaseOrderSelect = document.getElementById('purchaseOrderSelect');
        const poItemsBody = document.getElementById('poItemsBody');
        const itemCount = document.getElementById('itemCount');
        const saveReceipt = document.getElementById('saveReceipt');
        const formMessage = document.getElementById('formMessage');
        const initialPurchaseOrderId = @json($initialPurchaseOrderId);
        let lineIndex = 0;
        let loadSequence = 0;

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

        function makeCell(text, className = '') {
            const cell = document.createElement('td');
            cell.textContent = text;
            if (className) cell.className = className;
            return cell;
        }

        function updateLinesState() {
            const rows = [...poItemsBody.querySelectorAll('.po-item-row')];
            rows.forEach((row, index) => {
                row.querySelector('.row-number').textContent = index + 1;
            });
            itemCount.textContent = rows.length;
            saveReceipt.disabled = rows.length === 0;
        }

        function renderLines(lines) {
            poItemsBody.replaceChildren();
            lineIndex = 0;
            lines.forEach((line) => {
                const row = document.createElement('tr');
                row.className = 'po-item-row';
                row.append(makeCell('', 'row-number text-secondary'));
                row.append(makeCell(line.kode_barang || '—', 'small text-secondary'));
                row.append(makeCell(line.nama_barang || 'Barang tanpa nama', 'fw-semibold'));
                row.append(makeCell(`${formatQty(line.ordered_qty)} (sisa ${formatQty(line.remaining_qty)})`, 'text-end text-nowrap'));

                const qtyCell = document.createElement('td');
                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.name = `items[${lineIndex}][qty]`;
                qtyInput.className = 'form-control form-control-sm receive-qty';
                qtyInput.min = '0';
                qtyInput.max = line.remaining_qty;
                qtyInput.step = 'any';
                const oldItem = oldReceiptItems.find((item) => String(item.po_detail_id) === String(line.po_detail_id));
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
            const supplierOrders = purchaseOrders.filter((po) => po.supplier_id === supplierSelect.value);
            purchaseOrderSelect.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = supplierSelect.value ? '-- Pilih No. PO --' : '-- Pilih rekanan terlebih dahulu --';
            purchaseOrderSelect.append(placeholder);

            supplierOrders.forEach((po) => {
                const option = document.createElement('option');
                option.value = po.id;
                option.textContent = po.number || `PO #${po.id}`;
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

        updatePurchaseOrderOptions(initialPurchaseOrderId);
        if (purchaseOrderSelect.value) {
            loadPurchaseOrder(purchaseOrderSelect.value);
        } else if (supplierSelect.value) {
            poItemsBody.innerHTML = '<tr><td colspan="7" class="text-center text-secondary py-5">Pilih No. PO untuk memuat detail barang.</td></tr>';
        }
    </script>
</body>
</html>
