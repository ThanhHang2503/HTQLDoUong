<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Range Picker</title>
    <!-- Link tới các tập tin CSS của Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Link tới các tập tin CSS của DatePicker -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <label for="start_date">Ngày bắt đầu:</label>
                <input type="text" id="start_date" class="form-control">
            </div>
            <div class="col-md-6">
                <label for="end_date">Ngày kết thúc:</label>
                <input type="text" id="end_date" class="form-control">
            </div>
        </div>
    </div>

    <!-- Link tới các tập tin JavaScript của Bootstrap và DatePicker -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Kích hoạt DatePicker cho ô chọn ngày bắt đầu và ngày kết thúc
        $(document).ready(function() {
            $('#start_date').datepicker({
                format: 'yyyy-mm-dd', // Định dạng ngày YYYY-MM-DD
                autoclose: true
            });
            $('#end_date').datepicker({
                format: 'yyyy-mm-dd', // Định dạng ngày YYYY-MM-DD
                autoclose: true
            });
        });
    </script>

</body>

</html>