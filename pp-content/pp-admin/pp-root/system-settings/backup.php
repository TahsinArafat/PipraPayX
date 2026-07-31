<?php
if (!defined('PipraPay_INIT')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

    if (!canAccessPage(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    if (!hasPermission(json_decode($global_response_permission['response'][0]['permission'], true), 'system_settings', 'manage_backup', $global_user_response['response'][0]['role'])) {
        http_response_code(403);
        exit('Access denied. You need permission to perform this action. Please contact the admin.');
    }

    $backupDir = __DIR__ . '/../../../../pp-media/storage/backup/';
    $backups = [];

    if (is_dir($backupDir)) {
        $files = scandir($backupDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || !is_file($backupDir . $file)) continue;
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backupDir . $file))
            ];
        }
    }

    usort($backups, function ($a, $b) {
        return strcmp($b['name'], $a['name']);
    });

    function ppFormatBytes($bytes) {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
?>

<div class="page-header d-print-none" aria-label="Page header">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
            <!-- Page pre-title -->
                <div class="page-pretitle">
                    <ol class="breadcrumb breadcrumb-arrow mb-0">
                        <li class="breadcrumb-item"><a href="javascript:void(0)" onclick="load_content('System Settings','<?php echo $site_url.$path_admin ?>/system-settings','nav-item-system-settings')">System Settings</a></li>
                        <li class="breadcrumb-item active"><a href="javascript:void(0)">Backup &amp; Restore</a></li>
                    </ol>
                </div>
                <h2 class="page-title">Backup &amp; Restore</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-gs">
            <div class="col-12 col-xxl-4">
                <h2 class="card-title m-0 mb-1">Create Backup</h2>
                <p>Download a complete snapshot of your database as an SQL file.</p>

                <button class="btn btn-primary w-100 btn-create-backup">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database-export"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><ellipse cx="12" cy="6" rx="7" ry="3"></ellipse><path d="M4 6v6c0 1.657 3.582 3 8 3a19.84 19.84 0 0 0 3.302 -.267"></path><path d="M4 12v6c0 1.657 3.582 3 8 3c.414 0 .82 -.012 1.219 -.035"></path><path d="M19 16v6"></path><path d="M22 19l-3 -3l-3 3"></path></svg>
                    <span class="ms-1">Create Backup Now</span>
                </button>

                <div class="alert alert-warning mt-3 mb-0">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0"><svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-alert-triangle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.24 3.957l-8.422 14.06a1.989 1.989 0 0 0 1.7 2.983h16.845a1.989 1.989 0 0 0 1.7 -2.983l-8.423 -14.06a1.989 1.989 0 0 0 -3.4 0z" /><path d="M12 9v4" /><path d="M12 17h.01" /></svg></div>
                        <div class="flex-grow-1 ms-2">
                            <p class="mb-0"><strong>Warning:</strong> Restoring a backup will overwrite your current database. This action cannot be undone.</p>
                        </div>
                    </div>
                </div>

                <h2 class="card-title m-0 mt-4 mb-1">Restore Backup</h2>
                <p>Upload an SQL backup file to restore your database.</p>

                <div class="card p-2">
                    <div class="card-body">
                        <form action="" class="form-restore-backup" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="system-settings-backup-restore">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

                            <label class="form-label">Upload SQL File</label>

                            <div class="form-control-wrap mb-2">
                                <div class="input-group">
                                    <input type="file" name="backup_file" class="form-control" id="backup_file" accept=".sql" required>
                                </div>
                            </div>

                            <small class="form-hint">
                                Select an SQL backup file from your device and upload it to restore.
                            </small>

                            <button class="btn btn-danger btn-restore-backup mt-3 w-100" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-database-import"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><ellipse cx="12" cy="6" rx="7" ry="3"></ellipse><path d="M4 6v6c0 1.657 3.582 3 8 3a19.84 19.84 0 0 0 3.302 -.267"></path><path d="M4 12v6c0 1.657 3.582 3 8 3c.414 0 .82 -.012 1.219 -.035"></path><path d="M19 16v6"></path><path d="M22 19l-3 -3l-3 3"></path></svg>
                                <span class="ms-1">Restore Database</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xxl-8">
                <div class="card p-2">
                    <div class="card-header">
                        <h3 class="card-title">Available Backups</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($backups)) { ?>
                            <div class="text-center py-4 text-muted backup-empty">
                                <p class="mb-0">No backups found yet. Create your first backup to get started.</p>
                            </div>
                        <?php } else { ?>
                            <div class="table-responsive">
                                <table class="table table-vcenter card-table table-nowrap">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="backup-list">
                                        <?php foreach ($backups as $backup) { ?>
                                            <tr>
                                                <td class="text-break"><?= htmlspecialchars($backup['name']) ?></td>
                                                <td><?= ppFormatBytes($backup['size']) ?></td>
                                                <td><?= $backup['date'] ?></td>
                                                <td class="text-end">
                                                    <form action="<?php echo $site_url.$path_admin ?>/dashboard" method="post" style="display:inline;">
                                                        <input type="hidden" name="action" value="system-settings-backup-download">
                                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                                        <input type="hidden" name="backup_file" value="<?= htmlspecialchars($backup['name']) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
                                                            <span class="ms-1">Download</span>
                                                        </button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-backup" data-file="<?= htmlspecialchars($backup['name']) ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-trash"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0" /><path d="M10 11l0 6" /><path d="M14 11l0 6" /><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" /><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" /></svg>
                                                        <span class="ms-1">Delete</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script data-cfasync="false">
    function getBackupCsrf() {
        return $('input[name="csrf_token_default"]').val();
    }

    $('.btn-create-backup').click(function () {
        var btn = this;

        var btnClass = 'btn-create-backup';

        var btnInner = btn.innerHTML;

        btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url.$path_admin ?>/dashboard',
            data: {action: "system-settings-backup-create", csrf_token: getBackupCsrf() },
            dataType: 'json',
            success: function (response) {
                btn.innerHTML = btnInner;

                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = response.csrf_token;
                });
                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                if (response.status === 'true') {
                    location.reload();

                    createToast({
                        title: response.title,
                        description: response.message,
                        svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`,
                        timeout: 6000,
                        top: 70
                    });
                } else {
                    createToast({
                        title: response.title,
                        description: response.message,
                        svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                        timeout: 6000,
                        top: 70
                    });
                }
            },
            error: function (xhr, status, error) {
                btn.innerHTML = btnInner;

                createToast({
                    title: 'Something Wrong!',
                    description: 'For further assistance, please contact our support team.',
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            }
        });
    });

    $(document).on('click', '.btn-delete-backup', function () {
        var file = $(this).data('file');
        var btn = this;

        if (!confirm('Are you sure you want to delete this backup file?\n\n' + file)) {
            return;
        }

        var btnInner = btn.innerHTML;

        btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url.$path_admin ?>/dashboard',
            data: {action: "system-settings-backup-delete", csrf_token: getBackupCsrf(), backup_file: file },
            dataType: 'json',
            success: function (response) {
                btn.innerHTML = btnInner;

                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = response.csrf_token;
                });
                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                if (response.status === 'true') {
                    location.reload();
                }

                createToast({
                    title: response.title,
                    description: response.message,
                    svg: response.status === 'true'
                        ? `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            },
            error: function (xhr, status, error) {
                btn.innerHTML = btnInner;

                createToast({
                    title: 'Something Wrong!',
                    description: 'For further assistance, please contact our support team.',
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            }
        });
    });

    $('.form-restore-backup').submit(function (e) {
        e.preventDefault();

        if (!confirm('Restoring a backup will overwrite the current database. This action cannot be undone.\n\nAre you sure you want to continue?')) {
            return;
        }

        let formData = new FormData(this);

        var btnClass = 'btn-restore-backup';

        var btn = document.querySelector('.' + btnClass);

        var btnInner = btn.innerHTML;

        btn.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div>';

        $.ajax({
            type: 'POST',
            url: '<?php echo $site_url.$path_admin ?>/dashboard',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (response) {
                btn.innerHTML = btnInner;

                document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
                    input.value = response.csrf_token;
                });
                document.querySelectorAll('input[name="csrf_token_default"]').forEach(input => {
                    input.value = response.csrf_token;
                });

                if (response.status === 'true') {
                    document.querySelector(".form-restore-backup").reset();

                    location.reload();
                }

                createToast({
                    title: response.title,
                    description: response.message,
                    svg: response.status === 'true'
                        ? `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#5f38f9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-circle-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M9 12l2 2l4 -4" /></svg>`
                        : `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            },
            error: function (xhr, status, error) {
                btn.innerHTML = btnInner;

                createToast({
                    title: 'Something Wrong!',
                    description: 'For further assistance, please contact our support team.',
                    svg: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#d63939" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-exclamation-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 9v4" /><path d="M12 16v.01" /></svg>`,
                    timeout: 6000,
                    top: 70
                });
            }
        });
    });
</script>
