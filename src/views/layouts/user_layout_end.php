</div>
</div>
<!-- <p>&copy; Copyright 2024 by THLD Manage Database System </p> -->
<script src="javascript/sort.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var tables = document.querySelectorAll('.main table');

	tables.forEach(function (table) {
		var parent = table.parentElement;
		if (!parent) return;

		if (parent.classList.contains('product-table-scroll') || parent.classList.contains('content-table-scroll')) {
			return;
		}

		var wrapper = document.createElement('div');
		wrapper.className = 'content-table-scroll';
		parent.insertBefore(wrapper, table);
		wrapper.appendChild(table);
	});
});
</script>

