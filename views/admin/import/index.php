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
                    <i class="bi bi-upload me-1"></i>آپلود و ورود اطلاعات
                </button>
            </div>
        </form>
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
        document.getElementById('uploadProgress').classList.add('d-none');
        if (xhr.status === 200) {
            const res = JSON.parse(xhr.responseText);
            document.getElementById('resultMessage').textContent = res.message || (res.success ? 'عملیات با موفقیت انجام شد' : 'خطا در پردازش فایل');
            document.getElementById('resultDetail').textContent = res.errors && res.errors.length
                ? res.errors.slice(0, 3).join(' / ')
                : '';
            if (!res.success) {
                document.getElementById('resultIcon').className = 'bi bi-x-circle text-danger fs-1';
            } else {
                document.getElementById('resultIcon').className = 'bi bi-check-circle text-success fs-1';
            }
            new bootstrap.Modal(document.getElementById('importResultModal')).show();
        } else {
            showToast('خطا در ارتباط با سرور (' + xhr.status + ')', 'error');
        }
    };

    xhr.send(formData);
});

function resetUpload() {
    fileInput.value = '';
    selectedFileEl.classList.add('d-none');
    uploadBtn.classList.add('d-none');
    document.getElementById('uploadProgress').classList.add('d-none');
    document.getElementById('progressBar').style.width = '0%';
}
</script>
