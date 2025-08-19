<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connections.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) die("Invalid Product ID");

$product = $connection->query("SELECT * FROM products WHERE product_id=$id")->fetch_assoc();
if (!$product) die("Product not found");

$brands = $connection->query("SELECT * FROM brands");
$categories = $connection->query("SELECT * FROM categories");
$sizes = $connection->query("SELECT * FROM sizes")->fetch_all(MYSQLI_ASSOC);

$product_sizes_result = $connection->query("SELECT * FROM product_sizes WHERE product_id=$id");
$product_sizes = [];
while ($row = $product_sizes_result->fetch_assoc()) {
    $product_sizes[$row['size_id']] = $row['stock'];
}

$product_images = $connection->query("SELECT * FROM product_images WHERE product_id=$id ORDER BY is_default DESC")
                    ->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include('../includes/head.php'); ?>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
  <style>
    /* Light/Dark bridge to Bootstrap tokens so components auto-adapt */
    :root {
      --surface: var(--card, #fff);
      --surface-2: color-mix(in oklab, var(--surface) 90%, #0000);
      --text: var(--text, #0f172a);
      --muted: var(--muted, #475569);
      --border: var(--border, #e2e8f0);
      --primary: var(--primary, #4f46e5);
      --danger: var(--danger, #ef4444);
      --accent: var(--accent, #22c55e);

      /* Map into Bootstrap CSS variables */
      --bs-body-bg: var(--bg, #f5f7fb);
      --bs-body-color: var(--text);
      --bs-border-color: var(--border);
      --bs-primary: var(--primary);
      --bs-danger: var(--danger);
      --bs-success: var(--accent);
      --bs-link-color: var(--primary);
      --bs-link-hover-color: color-mix(in oklab, var(--primary) 85%, black);

      --radius: 1rem;
      --shadow: var(--shadow, 0 8px 24px rgba(2,6,23,.06));
    }

    body { -webkit-font-smoothing: antialiased; }

    .page-wrap { padding-block: clamp(12px, 2vw, 24px); }

    .card-smart { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); }
    .card-header { border-bottom: 1px solid var(--border); background: transparent; }

    /* Tabs become segmented control on small screens */
    @media (max-width: 576px) {
      .nav-tabs .nav-link { border: 1px solid var(--border); margin-bottom: .5rem; border-radius: .75rem; }
      .nav-tabs { border-bottom: 0; display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    }

    .editable { cursor: pointer; border: 1px dashed var(--border); border-radius: .5rem; padding: .5rem .75rem; background: var(--surface-2); }
    .editable:focus { outline: 2px solid color-mix(in oklab, var(--primary) 30%, transparent); }

    .inline-input { width: 100%; border-radius: .5rem; }

    .toast-container { z-index: 2000; }

    /* Image grid */
    .thumb { position: relative; overflow: hidden; border-radius: .75rem; border: 1px solid var(--border); }
    .thumb img { width: 100%; height: 120px; object-fit: cover; display: block; }
    .thumb .badge-default { position: absolute; top: .5rem; left: .5rem; }

    /* Dropzone */
    .dropzone { border: 2px dashed var(--border); border-radius: .75rem; padding: 1rem; background: var(--surface-2); text-align: center; }
    .dropzone.dragover { border-color: var(--primary); background: color-mix(in oklab, var(--surface-2) 90%, var(--primary)); }

    /* Sticky actions on mobile */
    .sticky-actions { position: sticky; bottom: 0; background: color-mix(in oklab, var(--surface) 85%, transparent); border-top: 1px solid var(--border); padding: .75rem; display: none; }
    @media (max-width: 576px) { .sticky-actions { display: block; } }

    /* Progress */
    .progress { height: .5rem; }
  </style>
</head>
<body>
<?php
  $page_subtitle = 'Product · Edit';
  include('../includes/sub_header.php');
?>

<div class="container page-wrap">
  <div class="card card-smart border-0">
    <div class="card-header d-flex flex-wrap align-items-center gap-2 justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="badge text-bg-primary rounded-pill">ID #<?= (int)$id ?></span>
        <h5 class="mb-0">Edit Product</h5>
      </div>
    </div>

    <div class="card-body">
      <!-- Alerts / Toasts -->
      <div class="toast-container position-fixed top-0 end-0 p-3" id="toaster"></div>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-4" id="editTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#generalTab" type="button" role="tab" aria-controls="generalTab" aria-selected="true"><i class="bi bi-sliders"></i> General</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="price-tab" data-bs-toggle="tab" data-bs-target="#pricingTab" type="button" role="tab" aria-controls="pricingTab" aria-selected="false"><i class="bi bi-cash-coin"></i> Price</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="sizes-tab" data-bs-toggle="tab" data-bs-target="#sizesTab" type="button" role="tab" aria-controls="sizesTab" aria-selected="false"><i class="bi bi-grid-3x3-gap"></i> Sizes</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#imagesTab" type="button" role="tab" aria-controls="imagesTab" aria-selected="false"><i class="bi bi-images"></i> Images</button>
        </li>
      </ul>

      <div class="tab-content" id="editTabContent">
        <!-- GENERAL TAB -->
        <div class="tab-pane fade show active" id="generalTab" role="tabpanel" aria-labelledby="general-tab" tabindex="0">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Product Name</label>
              <p class="editable" data-field="product_name" tabindex="0" role="textbox" aria-label="Edit product name"><?= htmlspecialchars($product['product_name']) ?></p>
            </div>

            <div class="col-md-6">
              <label class="form-label">Brand</label>
              <select class="form-select editable" data-field="brand_id" aria-label="Select brand">
                <?php while ($b = $brands->fetch_assoc()): ?>
                  <option value="<?= (int)$b['brand_id'] ?>" <?= ($product['brand_id'] == $b['brand_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['brand_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select class="form-select editable" data-field="category_id" aria-label="Select category">
                <?php while ($c = $categories->fetch_assoc()): ?>
                  <option value="<?= (int)$c['category_id'] ?>" <?= ($product['category_id'] == $c['category_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['category_name']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Active</label>
              <select class="form-select editable" data-field="is_active" aria-label="Toggle active">
                <option value="1" <?= $product['is_active'] ? 'selected' : '' ?>>Yes</option>
                <option value="0" <?= !$product['is_active'] ? 'selected' : '' ?>>No</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Description</label>
              <p class="editable" data-field="description" tabindex="0" role="textbox" aria-label="Edit description"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            </div>
          </div>
        </div>

        <!-- PRICE TAB -->
        <div class="tab-pane fade" id="pricingTab" role="tabpanel" aria-labelledby="price-tab" tabindex="0">
          <form id="priceForm" class="row g-3" novalidate>
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">

            <div class="col-6 col-md-3">
              <label class="form-label">Cost Price (₹)</label>
              <input type="number" inputmode="decimal" step="0.01" name="cost_price" class="form-control" min="0" value="<?= htmlspecialchars($product['cost_price']) ?>" required />
            </div>

            <div class="col-6 col-md-3">
              <label class="form-label">Profit (₹)</label>
              <input type="number" inputmode="decimal" step="0.01" name="profit_price" class="form-control" min="0" value="<?= htmlspecialchars($product['profit_price']) ?>" required />
            </div>

            <div class="col-12 col-md-3">
              <label class="form-label">Selling Price (₹)</label>
              <div class="form-control" id="sellingPrice" aria-live="polite"><?= number_format((float)$product['selling_price'], 2) ?></div>
            </div>

            <div class="col-12 d-flex gap-2">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Price</button>
              <button type="button" id="recalcBtn" class="btn btn-outline-secondary"><i class="bi bi-calculator"></i> Recalculate</button>
            </div>
          </form>

          <div class="sticky-actions mt-3">
            <button class="btn btn-primary w-100" form="priceForm"><i class="bi bi-save"></i> Save Price</button>
          </div>
        </div>

        <!-- SIZES TAB -->
        <div class="tab-pane fade" id="sizesTab" role="tabpanel" aria-labelledby="sizes-tab" tabindex="0">
          <form id="sizesForm" class="mt-2">
            <input type="hidden" name="product_id" value="<?= (int)$id ?>">

            <div class="row g-3 align-items-end">
              <?php foreach ($sizes as $s): $sid=(int)$s['size_id']; ?>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                  <div class="form-check d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" name="sizes[]" value="<?= $sid ?>" id="size-<?= $sid ?>" <?= isset($product_sizes[$sid]) ? 'checked' : '' ?> />
                    <label class="form-check-label" for="size-<?= $sid ?>"><?= htmlspecialchars($s['size_value']) ?></label>
                  </div>
                  <input type="number" name="size_stock[<?= $sid ?>]" class="form-control form-control-sm mt-1" value="<?= isset($product_sizes[$sid]) ? (int)$product_sizes[$sid] : '' ?>" placeholder="Stock" min="0" />
                </div>
              <?php endforeach; ?>

              <div class="col-6 col-md-3">
                <label class="form-label">Stock Hold</label>
                <p class="editable" data-field="stock_hold" tabindex="0" role="textbox"><?= (int)$product['stock_hold'] ?></p>
              </div>

              <div class="col-6 col-md-3">
                <label class="form-label">Total Stock</label>
                <p class="editable" data-field="stock" tabindex="0" role="textbox"><?= (int)$product['stock'] ?></p>
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Sizes</button>
              <button type="button" id="selectAllSizes" class="btn btn-outline-secondary"><i class="bi bi-check2-square"></i> Select All</button>
              <button type="button" id="clearAllSizes" class="btn btn-outline-secondary"><i class="bi bi-x-square"></i> Clear</button>
            </div>
          </form>

          <div class="sticky-actions mt-3">
            <button class="btn btn-primary w-100" form="sizesForm"><i class="bi bi-save"></i> Save Sizes</button>
          </div>
        </div>

        <!-- IMAGES TAB -->
        <div class="tab-pane fade" id="imagesTab" role="tabpanel" aria-labelledby="images-tab" tabindex="0">
          <div class="row g-3 mt-1">
            <div class="col-12">
              <div class="dropzone" id="dropzone" aria-label="Drop images here to upload">
                <div class="d-flex flex-column align-items-center gap-1">
                  <i class="bi bi-cloud-arrow-up" style="font-size: 2rem;"></i>
                  <div>Drag & drop images here or</div>
                  <form id="uploadForm" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 justify-content-center">
                    <input type="hidden" name="product_id" value="<?= (int)$id ?>">
                    <input type="file" name="images[]" id="imageInput" class="form-control" multiple accept="image/*">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload"></i> Upload</button>
                  </form>
                  <div class="w-100 mt-2 d-none" id="uploadProgressWrap">
                    <div class="progress" role="progressbar" aria-label="Upload progress" aria-valuemin="0" aria-valuemax="100">
                      <div class="progress-bar" id="uploadProgress" style="width:0%">0%</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-12">
              <div class="row g-3" id="gallery">
                <?php foreach ($product_images as $img): $url = htmlspecialchars($img['image_url']); ?>
                  <div class="col-6 col-sm-4 col-md-3 col-lg-2" id="img-<?= md5($url) ?>">
                    <div class="thumb">
                      <img src="../uploads/products/<?= $url ?>" alt="Product image" loading="lazy">
                      <?php if ($img['is_default']): ?>
                        <span class="badge text-bg-success badge-default">Default</span>
                      <?php endif; ?>
                    </div>
                    <div class="d-grid gap-1 mt-2">
                      <?php if (!$img['is_default']): ?>
                        <button type="button" class="btn btn-outline-primary btn-sm set-default-btn" data-img="<?= $url ?>"><i class="bi bi-star"></i> Set Default</button>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-primary btn-sm set-default-btn d-none" data-img="<?= $url ?>"><i class="bi bi-star"></i> Set Default</button>
                      <?php endif; ?>
                      <button type="button" class="btn btn-outline-danger btn-sm delete-img-btn" data-img="<?= $url ?>"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function bridgeBootstrapTheme(){
  // Keep Bootstrap's data-bs-theme in sync with our sub_header theme
  const root = document.documentElement;
  const sync = () => document.documentElement.setAttribute('data-bs-theme', root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
  const obs = new MutationObserver(sync); obs.observe(root, { attributes: true, attributeFilter: ['data-theme']});
  sync();
})();

function toast(msg, type='primary'){
  const cont = document.getElementById('toaster');
  const el = document.createElement('div');
  el.className = 'toast align-items-center text-bg-' + type + ' border-0';
  el.setAttribute('role','status');
  el.setAttribute('aria-live','polite');
  el.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>`;
  cont.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 2200 });
  t.show();
  el.addEventListener('hidden.bs.toast', ()=> el.remove());
}

// Inline editable paragraphs -> input on focus/Enter, save on blur/Enter, cancel on Escape
function attachEditableBehavior(p){
  p.addEventListener('click', () => toInput(p));
  p.addEventListener('keydown', (e)=>{ if (e.key === 'Enter'){ e.preventDefault(); p.blur(); } });
}

function toInput(p){
  const field = p.getAttribute('data-field');
  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'form-control inline-input';
  input.value = p.textContent.trim();
  p.replaceWith(input);
  input.focus();
  const cancel = (restore=true)=>{ if(restore){ input.replaceWith(p); attachEditableBehavior(p); } };
  input.addEventListener('keydown', e=>{ if(e.key==='Escape'){ cancel(true); } if(e.key==='Enter'){ input.blur(); }});
  input.addEventListener('blur', ()=>{
    const val = input.value.trim();
    updateField(field, val).then((ok)=>{
      p.textContent = val;
      cancel(true);
      toast(ok ? 'Updated successfully' : 'Update failed', ok ? 'success' : 'danger');
    }).catch(()=>{ cancel(true); toast('Server error','danger'); });
  });
}

document.querySelectorAll('p.editable').forEach(attachEditableBehavior);

document.querySelectorAll('select.editable').forEach(sel=>{
  sel.addEventListener('change', ()=>{
    const field = sel.getAttribute('data-field');
    updateField(field, sel.value).then(ok=> toast(ok ? 'Updated successfully' : 'Update failed', ok ? 'success' : 'danger'))
      .catch(()=> toast('Server error','danger'));
  });
});

function updateField(field, value){
  return fetch('update_inline.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=<?= (int)$id ?>&field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value)
  }).then(r=>r.text()).then(t => t.trim()==='OK');
}

// PRICE
const priceForm = document.getElementById('priceForm');
const sellingEl = document.getElementById('sellingPrice');

document.getElementById('recalcBtn').addEventListener('click', ()=>{
  const fd = new FormData(priceForm);
  const cost = parseFloat(fd.get('cost_price')||'0');
  const profit = parseFloat(fd.get('profit_price')||'0');
  const selling = cost + profit;
  sellingEl.textContent = isFinite(selling) ? selling.toFixed(2) : '0.00';
});

priceForm.addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(priceForm);
  fetch('update_price.php', { method:'POST', body: fd })
    .then(r=>r.text()).then(t=>{
      const tx = t.trim();
      if (tx === 'OK') {
        const cost = parseFloat(fd.get('cost_price')||'0');
        const profit = parseFloat(fd.get('profit_price')||'0');
        sellingEl.textContent = (cost + profit).toFixed(2);
        toast('Price saved','success');
      } else if (tx === 'NEGATIVE') {
        toast('Negative values not allowed','danger');
      } else {
        toast('Update failed','danger');
      }
    }).catch(()=> toast('Server error','danger'));
});

// SIZES
const sizesForm = document.getElementById('sizesForm');

document.getElementById('selectAllSizes').addEventListener('click', ()=>{
  sizesForm.querySelectorAll('input[type="checkbox"]').forEach(cb=> cb.checked = true);
});

document.getElementById('clearAllSizes').addEventListener('click', ()=>{
  sizesForm.querySelectorAll('input[type="checkbox"]').forEach(cb=> cb.checked = false);
});

sizesForm.addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(sizesForm);
  fetch('update_sizes.php', { method:'POST', body: fd })
  .then(r=>r.json())
  .then(data=>{
    if (data.status === 'OK'){
      document.querySelector('p[data-field="stock"]').textContent = data.total_stock;
      document.querySelector('p[data-field="stock_hold"]').textContent = data.stock_hold;
      toast('Sizes updated','success');
    } else {
      toast(data.msg || 'Failed to update sizes','danger');
    }
  }).catch(()=> toast('Server error','danger'));
});

// IMAGES - Dropzone + Upload with progress
const dz = document.getElementById('dropzone');
const uploadForm = document.getElementById('uploadForm');
const imageInput = document.getElementById('imageInput');
const progWrap = document.getElementById('uploadProgressWrap');
const progBar = document.getElementById('uploadProgress');
const gallery = document.getElementById('gallery');

;['dragenter','dragover'].forEach(evt => dz.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); }));
;['dragleave','drop'].forEach(evt => dz.addEventListener(evt, (e)=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); }));

dz.addEventListener('drop', (e)=>{
  const files = Array.from(e.dataTransfer.files || []).filter(f => f.type.startsWith('image/'));
  if (files.length){
    const dt = new DataTransfer();
    files.forEach(f=> dt.items.add(f));
    imageInput.files = dt.files; // populate file input
    uploadForm.requestSubmit();
  }
});

uploadForm.addEventListener('submit', function(e){
  e.preventDefault();
  if (!imageInput.files.length){ toast('Choose at least one image','danger'); return; }
  const fd = new FormData(uploadForm);

  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'upload_images.php');

  xhr.upload.addEventListener('progress', (e)=>{
    if (e.lengthComputable){
      const pct = Math.round((e.loaded / e.total) * 100);
      progWrap.classList.remove('d-none');
      progBar.style.width = pct + '%';
      progBar.textContent = pct + '%';
      progBar.setAttribute('aria-valuenow', String(pct));
    }
  });

  xhr.onload = function(){
    progWrap.classList.add('d-none');
    progBar.style.width = '0%'; progBar.textContent = '0%';
    try {
      const data = JSON.parse(xhr.responseText);
      if (data.status === 'OK'){
        toast('Images uploaded','success');
        // Reset input reliably
        uploadForm.reset();
        imageInput.value = '';
        // Append new images
        (data.files || []).forEach(addImageCard);
      } else {
        toast((data.msg || 'Upload failed'), 'danger');
      }
    } catch(err){ toast('Invalid server response','danger'); }
  };

  xhr.onerror = function(){ progWrap.classList.add('d-none'); toast('Network error','danger'); };
  xhr.send(fd);
});

function addImageCard(file){
  const id = 'img-' + (Math.random().toString(36).slice(2));
  const col = document.createElement('div');
  col.className = 'col-6 col-sm-4 col-md-3 col-lg-2';
  col.id = id;
  col.innerHTML = `
    <div class="thumb">
      <img src="../uploads/products/${encodeURIComponent(file)}" alt="Product image" loading="lazy">
    </div>
    <div class="d-grid gap-1 mt-2">
      <button type="button" class="btn btn-outline-primary btn-sm set-default-btn" data-img="${file}"><i class="bi bi-star"></i> Set Default</button>
      <button type="button" class="btn btn-outline-danger btn-sm delete-img-btn" data-img="${file}"><i class="bi bi-trash"></i> Delete</button>
    </div>`;
  gallery.prepend(col);
  col.querySelector('.set-default-btn').addEventListener('click', setDefaultHandler);
  col.querySelector('.delete-img-btn').addEventListener('click', deleteHandler);
}

function setDefaultHandler(){
  const btn = this;
  const imgUrl = btn.getAttribute('data-img');
  fetch('update_images.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=<?= (int)$id ?>&image_url=' + encodeURIComponent(imgUrl)
  }).then(r=>r.json())
    .then(data=>{
      if (data.status === 'OK'){
        toast('Default image updated','success');
        // Show all Set Default buttons
        document.querySelectorAll('.set-default-btn').forEach(b=> b.classList.remove('d-none'));
        // Remove any existing default badge
        document.querySelectorAll('.thumb .badge-default').forEach(b=> b.remove());
        // Hide current button and add badge
        btn.classList.add('d-none');
        const thumb = btn.closest('.col-6, .col-sm-4, .col-md-3, .col-lg-2').querySelector('.thumb');
        const badge = document.createElement('span');
        badge.className = 'badge text-bg-success badge-default';
        badge.textContent = 'Default';
        thumb.appendChild(badge);
      } else {
        toast(data.msg || 'Update failed','danger');
      }
    }).catch(()=> toast('Server error','danger'));
}

function deleteHandler(){
  const btn = this;
  const imgUrl = btn.getAttribute('data-img');
  if (!confirm('Delete image?')) return;
  const wrap = btn.closest('[id^="img-"]');
  fetch('delete_image.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'product_id=<?= (int)$id ?>&image_url=' + encodeURIComponent(imgUrl)
  }).then(r=>r.json())
    .then(data=>{
      if (data.status === 'OK'){
        toast('Image deleted','success');
        wrap.remove();
      } else {
        toast(data.msg || 'Delete failed','danger'); // Will show the "Please set another default first" message
      }
    }).catch(()=> toast('Server error','danger'));
}

document.querySelectorAll('.set-default-btn').forEach(b=> b.addEventListener('click', setDefaultHandler));
document.querySelectorAll('.delete-img-btn').forEach(b=> b.addEventListener('click', deleteHandler));
</script>
</body>
</html>
