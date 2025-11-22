<div class="row">
    <div class="col-12">
        <x-breadcrumb title="{{$title}}" current="{{$current}}"></x-breadcrumb>

        <div class="top-right-button-container">
            <div class="btn-group">
                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    EXPORTAR
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" id="dataTablesCopy" href="#">Copiar</a>
                    <a class="dropdown-item" id="dataTablesExcel" href="#">EXCEL</a>
                    <a class="dropdown-item" id="dataTablesCsv" href="#">CSV</a>
                    <a class="dropdown-item" id="dataTablesPdf" href="#">PDF</a>
                </div>
            </div>
            <button type="button" class="btn btn-primary createbtn" id="createbtn">NOVO</button>
        </div>

        <div class="mb-2">
            <div class="collapse dont-collapse-sm" id="displayOptions">
                <div class="d-block d-md-inline-block">
                    <div class="search-sm d-inline-block float-md-left mr-1 mb-1 align-top">
                        <input class="form-control" placeholder="Pesquisar..." id="searchDatatable">
                    </div>
                </div>
                <div class="float-md-right dropdown-as-select" id="pageCountDatatable">
                    <span class="text-muted text-small">Filtrar registros</span>
                    <button class="btn btn-outline-dark mx-2 btn-xs dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">25
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#">1</a>
                        <a class="dropdown-item" href="#">5</a>
                        <a class="dropdown-item" href="#">10</a>
                        <a class="dropdown-item active" href="#">25</a>
                        <a class="dropdown-item" href="#">50</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="separator"></div>
    </div>
</div>
