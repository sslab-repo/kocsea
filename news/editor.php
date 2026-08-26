<?php
// editor.php — shared TinyMCE setup for post_new.php / post_edit.php.
// Expects $editorFormId (id of the <form> that contains #newseditor).
$editorFormId = $editorFormId ?? 'postForm';
?>
<script src="https://cdn.tiny.cloud/1/fbzj4v8z1jl7zfa9iknthxw0tc3pnrl653qf8pqbb1xgewmr/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  (function () {
    const csrf = <?= json_encode(csrf_token()) ?>;
    const uploadUrl = <?= json_encode(base_url('upload_image.php')) ?>;

    tinymce.init({
      selector: '#newseditor',
      // Free plugins only — premium plugins fail on this API key
      plugins: 'lists link image table code',
      toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link image table | code',
      menubar: false,
      height: 400,
      convert_urls: false,
      // Image handling: adds the "Upload" tab to the image dialog and uploads pasted
      // or dropped images to upload_image.php instead of keeping blob:/base64 data.
      automatic_uploads: true,
      paste_data_images: true,
      images_reuse_filename: false,
      images_upload_handler: function (blobInfo) {
        const fd = new FormData();
        fd.append('file', blobInfo.blob(), blobInfo.filename());
        fd.append('csrf', csrf);
        return fetch(uploadUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(async r => {
            const j = await r.json().catch(() => ({}));
            if (!r.ok || !j.location) throw new Error(j.error || ('Upload failed: HTTP ' + r.status));
            return j.location;
          });
      },
      setup: function (editor) {
        editor.on('change input keyup', function () { editor.save(); });
      }
    });

    document.getElementById(<?= json_encode($editorFormId) ?>).addEventListener('submit', function () {
      if (tinymce && tinymce.triggerSave) tinymce.triggerSave();
    });
  })();
</script>
