<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Экспорт заявок</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
</head>

<body>

    <div class="mx-auto my-5 mx-3" style="width: 400px">
        <h1>Экспорт заявок</h1>
        <form method="post" onsubmit="return false" id="form">

            <div class="position-relative">
                
                <div class="mb-3">
                    <label for="start" class="form-label"><b>Дата начала периода <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control" id="start" name="start" required {{ request('start') ? 'value=' . request('start') : "" }}>
                </div>

                <div class="mb-3">
                    <label for="stop" class="form-label"><b>Дата окончания периода <span class="text-danger">*</span></b></label>
                    <input type="date" class="form-control" id="stop" name="stop" required {{ request('stop') ? 'value=' . request('stop') : "" }}>
                </div>

                <div class="mb-3">
                    <label for="city" class="form-label"><b>Город</b></label>
                    <select class="form-select" name="city[]" multiple>
                        {{-- <option {{ empty(request('city')) ? "selected" : "" }} value="">Выберите город</option> --}}
                        @foreach($cities as $city)
                        <option value="{{ $city }}" {{ in_array($city, request('city') ?? []) ? "selected" : "" }}>{{ $city }}</option>
                        @endforeach
                    </select>
                    <div id="city-help" class="form-text">Можно отфильтровать заявки по городу (по умолчаанию выбрана Москва)</div>
                </div>

                <div class="mb-3">
                    <label for="theme" class="form-label"><b>Тематика</b></label>
                    <select class="form-select" name="theme[]" multiple>
                        {{-- <option {{ empty(request('theme')) ? "selected" : "" }} value="">Выберите тематику</option> --}}
                        @foreach($themes as $theme)
                        <option value="{{ $theme }}" {{ in_array($theme, request('theme') ?? []) ? "selected" : "" }}>{{ $theme }}</option>
                        @endforeach
                    </select>
                    <div id="theme-help" class="form-text">Можно отфильтровать заявки по теме (по умолчанию будут выбраны все темы)</div>
                </div>

                <div class="position-absolute d-none justify-content-center align-items-center" id="loader" style="top: 0; left: 0; right: 0; bottom: 0; background-color: #ffffffc4;">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>

            </div>

            @csrf

            <div class="alert alert-danger d-none" id="message"></div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit" id="form-submit">Получить файл</button>
            </div>

        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-fbbOQedDUMZZ5KreZpsbe1LCZPVmfTnH7ois6mU1QK+m14rQ1l2bGBq41eYeM/fS" crossorigin="anonymous"></script>

    <script>
        $("#form").on("submit", function() {

            const data = $(this).serialize();
            $('#form-submit').prop('disabled', true);
            $('#loader').removeClass('d-none').addClass('d-flex');

            $.ajax({
                url: '/api/requiests/export?token={{ request('token') }}',
                method: 'post',
                data,
                dataType: 'binary',
		        xhrFields: {
			        'responseType': 'blob'
		        },
                success: function(data, status, xhr) {

                    $('#message').addClass('d-none');

			        var blob = new Blob([data], {type: xhr.getResponseHeader('Content-Type')});
			        var link = document.createElement('a');
			        link.href = window.URL.createObjectURL(blob);

                    let contentDispositions = String(xhr.getResponseHeader('Content-Disposition')).split(";");
                    if (typeof contentDispositions == "object" && contentDispositions.length > 1) {
                        let filename = String(contentDispositions[1] || "").split("=");
                        if (typeof filename[1] == "string") {
                            link.download = filename[1];
                        }
                    }

			        link.click();
		        },
                error: function (e) {
                    console.log(e);
                    $('#message').html(e?.responseJSON?.message || e.statusText).removeClass('d-none');
                },
                complete: () => {
                    $('#loader').removeClass('d-flex').addClass('d-none');
                    $('#form-submit').prop('disabled', false);
                }
            });
        });

    </script>

</body>

</html>
