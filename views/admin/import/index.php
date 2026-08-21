<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">وارد کردن فایل (ایمپورت)</h4>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-info-circle me-1"></i>راهنما</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <h6>فرمت فایل CSV:</h6>
                <div class="bg-light p-3 rounded font-monospace small" dir="ltr" style="overflow-x: auto;">
                    <code>Name,Date,Position,Mission,Link,Description,News,Exclusive News,Interview,Special Report,Photo,Gallery,Video</code><br>
                    <code>شخص تست,2026-08-20,خبرنگار,1,https://...,توضیح,2,0,0,0,0,0,0</code>
                </div>
            </div>
            <div class="col-md-6">
                <h6>نکات مهم:</h6>
                <ul class="small text-muted mb-0">
                    <li>فایل باید با UTF-8 ذخیره شده باشد</li>
                    <li>ردیف اول باید شامل نام ستون‌ها باشد (مانند نمونه بالا)</li>
                    <li>ستون <code>Name</code> برای یافتن/ایجاد کاربر استفاده می‌شود</li>
                    <li>تاریخ به فرمت میلادی <code>YYYY-MM-DD</code> یا جلالی <code>YYYY/MM/DD</code></li>
                    <li>تعداد و امتیاز به صورت خودکار محاسبه می‌شوند</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="POST" action="<?= url('admin/import/process') ?>" enctype="multipart/form-data" id="uploadForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="upload-area border border-2 border-dashed rounded-3 p-5 text-center" id="dropZone"
                 style="cursor: pointer; transition: all 0.3s;">
                <i class="bi bi-cloud-arrow-up fs-1 text-primary d-block mb-2"></i>
                <h5 class="mb-2">فایل را اینجا رها کنید</h5>
                <p class="text-muted mb-3">یا کلیک کنید تا فایل را انتخاب کنید</p>
                <input type="file" name="import_file" id="fileInput" class="d-none"
                       accept=".csv" required>
                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                    <i class="bi bi-folder2-open me-1"></i>انتخاب فایل
                </button>
                <div id="selectedFile" class="mt-3 d-none">
                    <span class="badge bg-success fs-6"><i class="bi bi-file-earmark-check me-1"></i><span id="fileName"></span></span>
                </div>
            </div>

            <div id="uploadProgress" class="mt-3 d-none">
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         id="progressBar" style="width: 0%">0%</div>
                </div>
                <small class="text-muted mt-1 d-block" id="progressText">در حال آپلود...</small>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success d-none" id="uploadBtn">
                    <i class="bi bi-upload me-1"></i>آپلود و پیش‌نمایش
                </button>
            </div>
        </form>
    </div>
</div>

<div id="previewSection" class="d-none">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table me-1"></i>پیش‌نمایش داده‌ها</h5>
            <div class="d-flex gap-2">
                <span class="badge bg-info fs-6" id="previewCount">0 ردیف</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle" id="previewTable">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>کاربر</th>
                        <th>نوع</th>
                        <th>تاریخ</th>
                        <th>تعداد</th>
                        <th>عنوان</th>
                        <th>امتیاز</th>
                        <th class="text-center">انتخاب</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll" checked>
                <label class="form-check-label" for="selectAll">انتخاب همه</label>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-success" id="importBtn" onclick="doImport()">
                    <i class="bi bi-check2-all me-1"></i>وارد کردن انتخاب شده
                </button>
                <button class="btn btn-outline-secondary" onclick="resetUpload()">
                    <i class="bi bi-x-lg me-1"></i>لغو
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importProgressModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">در حال پردازش...</span>
                </div>
                <h5 id="importStatus">در حال وارد کردن اطلاعات...</h5>
                <div class="progress mt-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="importProgress" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="resultTitle">نتیجه ایمپورت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <i class="bi bi-check-circle text-success fs-1" id="resultIcon"></i>
                <h4 class="mt-3" id="resultMessage"></h4>
                <p class="text-muted" id="resultDetail"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <a href="<?= url('admin/activities') ?>" class="btn btn-primary">مشاهده فعالیت‌ها</a>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" onclick="resetUpload()">بستن</button>
            </div>
        </div>
    </div>
</div>

<style>
.upload-area:hover, .upload-area.drag-over {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
}
</style>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const selectedFileEl = document.getElementById('selectedFile');
const uploadBtn = document.getElementById('uploadBtn');

dropZone.addEventListener('click', function(e) {
    if (e.target === dropZone || e.target.closest('.upload-area') && !e.target.closest('button')) {
        fileInput.click();
    }
});

dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('drag-over');
});

dropZone.addEventListener('dragleave', function() {
    this.classList.remove('drag-over');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        onFileSelected(e.dataTransfer.files[0]);
    }
});

fileInput.addEventListener('change', function() {
    if (this.files.length) onFileSelected(this.files[0]);
});

function onFileSelected(file) {
    document.getElementById('fileName').textContent = file.name;
    selectedFileEl.classList.remove('d-none');
    uploadBtn.classList.remove('d-none');
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const progress = document.getElementById('uploadProgress');
    const progressBar = document.getElementById('progressBar');
    progress.classList.remove('d-none');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', this.action);
    xhr.setRequestHeader('X-CSRF-TOKEN', '<?= csrf_token() ?>');

    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const pct = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = pct + '%';
            progressBar.textContent = pct + '%';
        }
    });

    xhr.onload = function() {
        if (xhr.status === 200) {
            const res = JSON.parse(xhr.responseText);
            if (res.success) {
                const msg = 'پردازش کامل شد: ' + (res.success_count ?? 0) + ' رکورد موفق، ' + (res.error_count ?? 0) + ' رکورد خطا';
                showToast(msg, 'success');
                if (res.errors && res.errors.length) {
                    showToast('اولین خطا: ' + res.errors[0], 'warning');
                }
            } else {
                showToast(res.message || 'خطا در پردازش فایل', 'error');
            }
        } else {
            showToast('خطا در ارتباط با سرور (' + xhr.status + ')', 'error');
        }
    };

    xhr.send(formData);
});

function showPreview(data) {
    const previewSection = document.getElementById('previewSection');
    const previewBody = document.getElementById('previewBody');
    previewBody.innerHTML = '';

    document.getElementById('previewCount').textContent = data.length + ' ردیف';

    data.forEach(function(row, i) {
        const esc = function(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        };
        const tr = document.createElement('tr');
        tr.innerHTML = '<td>' + (i + 1) + '</td>'
            + '<td>' + esc(row.user_name || row.user_id) + '</td>'
            + '<td><span class="badge bg-secondary">' + esc(row.type_name || row.type_code) + '</span></td>'
            + '<td>' + esc(row.date || '-') + '</td>'
            + '<td class="text-center">' + (row.quantity || 1) + '</td>'
            + '<td>' + esc(row.title || '-') + '</td>'
            + '<td class="text-center fw-bold">' + (row.score || 0) + '</td>'
            + '<td class="text-center"><input type="checkbox" class="form-check-input import-row" checked></td>';
        previewBody.appendChild(tr);
    });

    previewSection.classList.remove('d-none');
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('.import-row').forEach(function(cb) {
            cb.checked = document.getElementById('selectAll').checked;
        });
    });
}

function doImport() {
    const modal = new bootstrap.Modal(document.getElementById('importProgressModal'));
    modal.show();

    fetch('<?= url("admin/import/process") ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= csrf_token() ?>'
        },
        body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(function(res) {
        modal.hide();
        document.getElementById('resultMessage').textContent = res.message || 'عملیات با موفقیت انجام شد';
        document.getElementById('resultDetail').textContent = res.detail || '';
        if (!res.success) {
            document.getElementById('resultIcon').className = 'bi bi-x-circle text-danger fs-1';
        } else {
            document.getElementById('resultIcon').className = 'bi bi-check-circle text-success fs-1';
        }
        new bootstrap.Modal(document.getElementById('importResultModal')).show();
    });
}

function resetUpload() {
    fileInput.value = '';
    selectedFileEl.classList.add('d-none');
    uploadBtn.classList.add('d-none');
    document.getElementById('previewSection').classList.add('d-none');
    document.getElementById('uploadProgress').classList.add('d-none');
    document.getElementById('progressBar').style.width = '0%';
}
</script>
