<div id="confirmationRejectModal" class="modal fade show" tabindex="-1" role="dialog"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="modal-title-notification">Alerta de atenção!</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="py-3 text-center">
                    <i class="iconsminds-idea text-primary" style="font-size: 96px;"></i>
                    <h4 class="text-gradient text-primary mt-4">Você tem certeza?</h4>
                    <p class="mx-2">Caso rejeite o orçamento, não será contabilizado os valores em projeções de caixa.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmReject">Sim, rejeitar orçamento!</button>
            </div>
        </div>
    </div>
</div>

<style>
    #confirmationModal .modal-header {
        border-bottom: none;
    }
</style>
