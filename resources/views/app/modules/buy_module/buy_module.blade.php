<x-app-layout>
    @section('content')

        <div class="container mt-5">
            <!-- Steps Navigation -->
            <div class="d-flex justify-content-between mb-4">
                <div id="step-1-header" class="step-header">
                    <h5 class="mb-1">Módulo</h5>
                    <span class="text-muted">Informações do Módulo</span>
                </div>
                <div id="step-2-header" class="step-header text-muted">
                    <h5 class="mb-1">Pagamento</h5>
                    <span>Forma de Pagamento</span>
                </div>
            </div>

            <!-- Step 1: Module Information -->
            <div id="step-1" class="card">
                <div class="card-body">
                    <h5>Informações do Módulo</h5>
                    <p>Insira as informações necessárias sobre o módulo.</p>
                    <button class="btn btn-danger" id="next-step-1">Próximo</button>
                </div>
            </div>

            <!-- Step 2: Payment Method -->
            <div id="step-2" class="card d-none">
                <div class="card-body">

{{--                    @if($transaction)--}}
                        <ul class="nav nav-tabs mb-3" id="paymentTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="pix-tab" data-bs-toggle="tab" href="#pix" role="tab"
                                   aria-controls="pix" aria-selected="true">PIX</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="credit-tab" data-bs-toggle="tab" href="#credit" role="tab"
                                   aria-controls="credit" aria-selected="false">Cartão de Crédito</a>
                            </li>
                        </ul>
                        <div class="tab-content" id="paymentTabContent">
                            <!-- PIX Tab -->
                            <div class="tab-pane fade show active" id="pix" role="tabpanel" aria-labelledby="pix-tab">
                                <form id="pixForm">
                                    @csrf

                                    <!-- Campos ocultos para informações do módulo e do usuário -->
                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                                    <button type="button" id="generateQrCodeBtn" class="btn btn-outline-primary">Gerar
                                        QRCode
                                    </button>
                                </form>

                                <div id="qrCodeContainer" style="margin-top: 20px;"></div>
                                <span id="span-pix-copia-cola"
                                      style="margin-left: 8px;"><strong>PIX copia e cola: </strong><p
                                        id="pix-copia-cola"
                                        style="margin-left: 8px;"></p></span>
                                <span id="span-due-date" style="margin-left: 8px;"><strong>Válido até: </strong><p
                                        id="due-date"
                                        style="margin-left: 8px;"></p></span>
                                <span id="span-payment-status"
                                      style="margin-left: 8px;"><strong>Status do pagamento: </strong><p
                                        id="payment-status"
                                        style="margin-left: 8px;"></p></span>
                            </div>

                            <!-- Credit Card Tab -->
                            <div class="tab-pane fade" id="credit" role="tabpanel" aria-labelledby="credit-tab">
                                <form id="credit-form" action="{{ route('cartao-credito.module') }}" method="POST">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="card-number" class="form-label">Número do Cartão</label>
                                        <input type="text" class="form-control" id="card-number" name="card_number"
                                               required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="card-holder" class="form-label">Titular do Cartão</label>
                                        <input type="text" class="form-control" id="card-holder" name="card_holder"
                                               required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="card-expiry" class="form-label">Validade do Cartão</label>
                                        <input type="text" class="form-control" id="card-expiry" name="card_expiry"
                                               placeholder="MM/AA" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="card-cvv" class="form-label">Código de Segurança (CVV)</label>
                                        <input type="text" class="form-control" id="card-cvv" name="card_cvv" required>
                                    </div>
                                </form>
                            </div>

                        </div>

                    <!-- Navigation Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <button class="btn btn-danger" id="prev-step-2">Anterior</button>
                        <button class="btn btn-danger d-none" id="finalize">Finalizar</button>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            $(document).ready(function () {
                var customerId = "{{Auth::user()->customerLogin->customer->customerId}}";

                $('#finalize').click(function() {
                    windows.reload();
                });

                $('#generateQrCodeBtn').click(function () {
                    var formData = $('#pixForm').serialize();

                    $.ajax({
                        url: "{{ route('gerar-qrcode.module') }}",
                        type: "POST",
                        data: formData,
                        success: function (response) {
                            var payment = response.data[customerId];

                            if (response.success) {
                                $('#qrCodeContainer').html('<img src="data:image/png;base64,' + payment.qrCode.encodedImage + '" alt="QR Code">');
                                $('#pix-copia-cola').html(payment.qrCode.payload);
                                $('#due-date').html(payment.qrCode.expirationDate);
                                $('#payment-status').html(payment.status);
                                $('#generateQrCodeBtn').hide();

                                verificarStatusPagamento(payment.payment_id);
                            } else {
                                alert('Erro ao gerar o QRCode. Tente novamente.');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Erro ao gerar o QRCode:', error);
                            alert('Erro ao gerar o QRCode. Tente novamente.');
                        }
                    });
                });

                function verificarStatusPagamento(paymentId) {
                    var checkInterval = setInterval(function () {
                        $.ajax({
                            url: "/modules/buy-module/" +
                                "checar-status-pagamento/" + paymentId,
                            type: "GET",
                            success: function (response) {
                                if (response.success) {
                                    if (response.status === 'RECEIVED') {

                                        $('#payment-status').html('Pagamento confirmado!');
                                        $('#finalize').show();
                                        $('#qrCodeContainer').hide();
                                        $('#span-pix-copia-cola').hide();
                                        $('#span-due-date').hide();

                                        window.location.href = "/dashboard";

                                        clearInterval(checkInterval);
                                    } else if (response.status === 'PENDING') {
                                        // Pagamento ainda está pendente
                                        console.log('Pagamento pendente, verificando novamente...');
                                    } else {
                                        // Outro status ou falha
                                        console.log('Status do pagamento: ', response.status);
                                    }
                                } else {
                                    console.error('Erro ao verificar status do pagamento:', response.message);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Erro ao verificar status do pagamento:', error);
                            }
                        });
                    }, 3000);
                }
            });

        </script>

        <script>
            // JavaScript for step navigation
            document.getElementById('next-step-1').addEventListener('click', function () {
                document.getElementById('step-1').classList.add('d-none');
                document.getElementById('step-2').classList.remove('d-none');
                document.getElementById('step-1-header').classList.add('text-muted');
                document.getElementById('step-2-header').classList.remove('text-muted');
                document.getElementById('step-2-header').classList.add('text-primary');
            });

            document.getElementById('prev-step-2').addEventListener('click', function () {
                document.getElementById('step-2').classList.add('d-none');
                document.getElementById('step-1').classList.remove('d-none');
                document.getElementById('step-2-header').classList.add('text-muted');
                document.getElementById('step-2-header').classList.remove('text-primary');
                document.getElementById('step-1-header').classList.remove('text-muted');
            });

            // JavaScript for form submission
            document.getElementById('finalize').addEventListener('click', function () {
                const activeTab = document.querySelector('.tab-pane.active');
                if (activeTab.id === 'credit') {
                    document.getElementById('credit-form').submit();
                }
            });
        </script>
    @endsection
</x-app-layout>
