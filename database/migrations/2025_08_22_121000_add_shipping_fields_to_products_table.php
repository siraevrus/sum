<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'shipping_location')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('shipping_location')->nullable()->after('producer');
            });
        }

        if (! Schema::hasColumn('products', 'shipping_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('shipping_date')->nullable()->after('shipping_location');
            });
        }

        if (! Schema::hasColumn('products', 'tracking_number')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('tracking_number')->nullable()->after('transport_number');
            });
        }

        if (! Schema::hasColumn('products', 'expected_arrival_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('expected_arrival_date')->nullable()->after('shipping_date');
            });
        }

        if (! Schema::hasColumn('products', 'actual_arrival_date')) {
            Schema::table('products', function (Blueprint $table) {
                $table->date('actual_arrival_date')->nullable()->after('expected_arrival_date');
            });
        }

        if (! Schema::hasColumn('products', 'notes')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('products', 'document_path')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('document_path')->nullable()->after('notes');
            });
        }
    }

    public function down(): void
    {
        $columns = [
            'shipping_location',
            'shipping_date',
            'tracking_number',
            'expected_arrival_date',
            'actual_arrival_date',
            'document_path',
            'notes',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('products', $column)) {
                Schema::table('products', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
