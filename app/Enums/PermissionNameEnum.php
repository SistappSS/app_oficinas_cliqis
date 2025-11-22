<?php

namespace App\Enums;

enum PermissionNameEnum: string
{
    case dashboard = 'Painel';
    case dashboard_finance = 'Painel Financeiro';
    case dashboard_stock = 'Painel de Estoque';
    case dashboard_sales = 'Painel de Vendas';

    case entitie_user = 'Usuário';
    case entitie_customer = 'Cliente';
    case entitie_supplier = 'Fornecedor';
    case entitie_partner = 'Parceiro';
    case entitie_representative = 'Representante';

    case sales_service = 'Serviço';
    case sales_budget = 'Orçamento';
    case sales_contract = 'Contrato';
    case sales_portfolio = 'Portfólio';
    case sales_contracted_plan = 'Plano Contratado';

    case finance_accounts_payable = 'Contas a Pagar';
    case finance_accounts_receivable = 'Contas a Receber';
    case finance_account_projection = 'Projeção Financeira';
    case finance_account_manager = 'Gestão Financeira';

    case shop_control_insume = 'Controle de Insumos';
    case shop_purchase_order = 'Pedido de Compra';

    case inventory_category = 'Categoria de Inventário';
    case inventory_sub_category = 'Subcategoria de Inventário';
    case inventory_product = 'Produto';
    case stock_manager = 'Gestão de Estoque';
    case product_historic = 'Histórico de Produto';

    case project_status_task = 'Status de Tarefa';
    case project_task = 'Tarefa';

    case support_ticket = 'Ticket de Suporte';
    case support_chat = 'Chat de Suporte';
    case support_forum = 'Fórum de Suporte';

    case rh_employee = 'Funcionário';
    case rh_salary_benefit = 'Salário e Benefícios';
    case rh_manager = 'Gestão de RH';

    case report = 'Relatório';

    case email = 'E-mail';

    case config = 'Configuração';

    case update = 'Atualização';

    public static function getTranslatedPermission(string $permission): ?string
    {
        foreach (self::cases() as $enumCase) {
            if ($enumCase->name === $permission) {
                return $enumCase->value;
            }
        }

        return null;
    }
}



