<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_node_id')->constrained('learning_nodes')->cascadeOnDelete();
            $table->foreignId('target_node_id')->constrained('learning_nodes')->cascadeOnDelete();
            $table->string('type')->default('prerequisite');
            $table->timestamps();

            $table->unique(['source_node_id', 'target_node_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_relations');
    }
};
