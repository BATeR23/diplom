<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Обновляем существующие enum значения для роли, если используется enum
        // Если role - это просто string, то ничего не нужно делать
        // Просто убедимся, что в коде поддерживается роль 'manager'
    }

    public function down(): void
    {
        // Откат не требуется, так как мы только добавляем поддержку новой роли
    }
};

