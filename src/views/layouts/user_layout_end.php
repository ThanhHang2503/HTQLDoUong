</div>
</div>
<!-- Shared Notification Modal -->
<div class="modal fade" id="notifyModal" tabindex="-1" aria-labelledby="notifyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header py-2 px-3" id="notifyModalHeader">
        <h5 class="modal-title d-flex align-items-center gap-2" id="notifyModalLabel">
          <i id="notifyModalIcon"></i>
          <span id="notifyModalTitle"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-3 py-3" id="notifyModalBody"></div>
      <div class="modal-footer py-2 px-3">
        <button type="button" class="btn btn-sm px-4" id="notifyModalBtn" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>
<!-- Copyright -->
<!-- <p>&copy; Copyright 2024 by THLD Manage Database System </p> -->
<script src="javascript/sort.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	// --- Table scroll wrapper ---
	var tables = document.querySelectorAll('.main table');
	tables.forEach(function (table) {
		var parent = table.parentElement;
		if (!parent) return;
		if (parent.classList.contains('product-table-scroll') || parent.classList.contains('content-table-scroll') || parent.classList.contains('top-cust-scroll')) { return; }
		var wrapper = document.createElement('div');
		wrapper.className = 'content-table-scroll';
		parent.insertBefore(wrapper, table);
		wrapper.appendChild(table);
	});

	// --- Shared Notification Modal ---
	// Usage in PHP: echo notifyData('success', 'Title', 'Message') on the page container
	var msg   = document.body.dataset.notifyMessage   || '';
	var type  = document.body.dataset.notifyType      || 'success';
	var title = document.body.dataset.notifyTitle     || (type === 'success' ? 'Thành công' : (type === 'error' ? 'Lỗi' : 'Thông báo'));

	if (msg) {
		var configs = {
			success: { header: 'bg-success text-white', icon: 'fa-solid fa-circle-check', btn: 'btn-success' },
			error:   { header: 'bg-danger text-white',  icon: 'fa-solid fa-circle-xmark', btn: 'btn-danger'  },
			info:    { header: 'bg-info text-white',    icon: 'fa-solid fa-circle-info',  btn: 'btn-info'    },
			warning: { header: 'bg-warning text-dark',  icon: 'fa-solid fa-triangle-exclamation', btn: 'btn-warning' }
		};
		var cfg = configs[type] || configs.info;
		document.getElementById('notifyModalHeader').className = 'modal-header py-2 px-3 ' + cfg.header;
		document.getElementById('notifyModalIcon').className   = cfg.icon;
		document.getElementById('notifyModalTitle').textContent = title;
		document.getElementById('notifyModalBody').textContent  = msg;
		document.getElementById('notifyModalBtn').className    = 'btn btn-sm px-4 ' + cfg.btn;
		new bootstrap.Modal(document.getElementById('notifyModal')).show();
	}
});

// Global helper: call from any page JS to show notification
function showNotify(message, type, title) {
	var configs = {
		success: { header: 'bg-success text-white', icon: 'fa-solid fa-circle-check', btn: 'btn-success' },
		error:   { header: 'bg-danger text-white',  icon: 'fa-solid fa-circle-xmark', btn: 'btn-danger'  },
		info:    { header: 'bg-info text-white',    icon: 'fa-solid fa-circle-info',  btn: 'btn-info'    },
		warning: { header: 'bg-warning text-dark',  icon: 'fa-solid fa-triangle-exclamation', btn: 'btn-warning' }
	};
	var cfg = configs[type] || configs.info;
	var defTitle = { success:'Thành công', error:'Lỗi', warning:'Cảnh báo', info:'Thông báo' };
	document.getElementById('notifyModalHeader').className = 'modal-header py-2 px-3 ' + cfg.header;
	document.getElementById('notifyModalIcon').className   = cfg.icon;
	document.getElementById('notifyModalTitle').textContent = title || defTitle[type] || 'Thông báo';
	document.getElementById('notifyModalBody').textContent  = message;
	document.getElementById('notifyModalBtn').className    = 'btn btn-sm px-4 ' + cfg.btn;
	new bootstrap.Modal(document.getElementById('notifyModal')).show();
}
</script>
