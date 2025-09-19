<?php

namespace App;

enum UserRole: string
{
    case ADMIN = 'admin';
    case OPERATOR = 'operator';
    case WAREHOUSE_WORKER = 'warehouse_worker';
    case SALES_MANAGER = 'sales_manager';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Администратор',
            self::OPERATOR => 'Оператор ПК',
            self::WAREHOUSE_WORKER => 'Работник склада',
            self::SALES_MANAGER => 'Менеджер по продажам',
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'dashboard',
                'companies',
                'employees',
                'products',
                'requests',
                'inventory',
                'products_in_transit',
                'sales',
                'product_receipt',
                'product_templates',
            ],
            self::OPERATOR => [
                'products',
                'inventory',
                'products_in_transit',
            ],
            self::WAREHOUSE_WORKER => [
                'requests',
                'inventory',
                'products_in_transit',
                'sales',
                'product_receipt',
            ],
            self::SALES_MANAGER => [
                'requests',
                'inventory',
                'products_in_transit',
            ],
        };
    }
}
