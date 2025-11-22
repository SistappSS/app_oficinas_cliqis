<x-modal modalId="modalUser" formId="formUser" :input="$input">
    <div class="row">
        <x-input col="12" set="" id="name" name="name" type="text" label="Nome do usuário"
                 placeholder="John Doe"></x-input>
    </div>

    <div class="row">
        <x-input col="12" set="" id="email" name="email" type="email" label="E-mail de acesso"
                 placeholder="john@sistapp.com.br"></x-input>
    </div>

    <div class="row">
        <x-input col="12 col-sm-6" set="" id="password" name="password" type="password" label="Senha de acesso"
                 placeholder="********"></x-input>
        <x-input col="12 col-sm-6" set="" id="password_confirmation" name="password_confirmation" type="password"
                 label="Confirmar senha" placeholder="********"></x-input>
    </div>

        <div class="row m-0 mb-2">
            <x-check-input col="4" id="is_active" name="is_active" type="checkbox" label="Usuário ativo?" check=""></x-check-input>
        </div>
    @if(\Auth::user()->hasRole('admin'))

        <div class="row mt-1 mb-3 mx-1 d-flex flex-column">
            <label class="text-muted fw-bold p-0 mb-1">Permissão</label>

            <x-radio-input col="12" id="admin" name="role" type="radio" label="Administrador"></x-radio-input>
            <x-radio-input col="12" id="free" name="role" type="radio" label="Gratuito" check="1"></x-radio-input>
            <x-radio-input col="12" id="web_design" name="role" type="radio" label="Web Design/Freelancer"></x-radio-input>
            <x-radio-input col="12" id="business" name="role" type="radio" label="Micro Empresa"></x-radio-input>
            <x-radio-input col="12" id="authorized" name="role" type="radio" label="Autorizada"></x-radio-input>
        </div>
        @else
        <div class="row mt-1 mb-3 mx-1 d-flex flex-column">
            <label class="text-muted fw-bold p-0 mb-1">Permissão</label>

            <x-radio-input col="12" id="admin" name="role" type="radio" label="Visualizar caixa"></x-radio-input>
            <x-radio-input col="12" id="free" name="role" type="radio" label="Abrir caixa" check="1"></x-radio-input>
            <x-radio-input col="12" id="web_design" name="role" type="radio" label="Cadastrar produtos"></x-radio-input>
            <x-radio-input col="12" id="business" name="role" type="radio" label="Micro Empresa"></x-radio-input>
            <x-radio-input col="12" id="authorized" name="role" type="radio" label="Autorizada"></x-radio-input>
        </div>
    @endcan

    <div class="row">
        <div class="col-6 offset-3 d-flex justify-content-center align-items-baseline">
            <label for="image" class="circle-label" id="imagemLabel">
                <input type="file" class="form-control form-control-sm d-none" id="image" name="image" onchange="displayImage(this)">
                <div class="image-container">
                    <i id="cameraIcon" class="fa-solid fa-camera"></i>
                    <img id="uploadedImage" class="uploaded-image" alt="Imagem anexada">
                </div>
            </label>
        </div>
    </div>
</x-modal>

<style>
    .circle-label {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 125px;
        height: 125px;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        border: 1px solid #000;
    }

    .circle-label i {
        font-size: 28px;
        color: #000;
        filter: drop-shadow(1px 1px 5px #000);
    }

    .circle-label img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        position: absolute;
        top: 0;
        left: 0;
        display: none;
    }
</style>

<script>
    function openFileInput() {
        document.getElementById('image').click();
    }

    document.getElementById('image').addEventListener('change', function () {
        displayImage(this);
    });

    function displayImage(inputElement) {
        var file = inputElement.files[0];
        var cameraIcon = document.getElementById('cameraIcon');
        var uploadedImage = document.getElementById('uploadedImage');

        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                // Exibe a imagem no círculo
                uploadedImage.src = e.target.result;
                cameraIcon.style.display = 'none';
                uploadedImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }
</script>
