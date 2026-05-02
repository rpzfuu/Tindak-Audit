<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS tindakaudit');

        Schema::create('tindakaudit.bidang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('tindakaudit.spi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nik', 15)->unique();
        });

        Schema::create('tindakaudit.temuan', function (Blueprint $table) {
            $table->increments('id');
            $table->string('created_by', 15)->index();
            $table->text('temuan');
            $table->string('kode_unit', 10)->index();
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unsignedInteger('bidang_id')->index();
            $table->string('kode_bagian')->nullable()->index();
            $table->string('kode_subbagian')->nullable()->index();

            $table->foreign('bidang_id')
                ->references('id')
                ->on('tindakaudit.bidang')
                ->restrictOnDelete();
        });

        Schema::create('tindakaudit.temuan_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('temuan_id')->index();
            $table->text('temuan');
            $table->string('status')->index();
            $table->timestamps();
            $table->string('changed_by', 15)->nullable()->index();
            $table->string('kode_unit', 10)->index();
            $table->unsignedInteger('bidang_id')->index();
            $table->string('kode_bagian')->nullable()->index();
            $table->string('keterangan')->nullable();
            $table->string('action')->nullable()->index();

            $table->foreign('temuan_id')
                ->references('id')
                ->on('tindakaudit.temuan')
                ->cascadeOnDelete();
            $table->foreign('bidang_id')
                ->references('id')
                ->on('tindakaudit.bidang')
                ->restrictOnDelete();
        });

        Schema::create('tindakaudit.rekomendasi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('temuan_id')->index();
            $table->text('rekomendasi');
            $table->timestamps();
            $table->string('status')->default('Menunggu Tindak Lanjut')->index();
            $table->text('alasan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->string('bukti')->nullable();

            $table->foreign('temuan_id')
                ->references('id')
                ->on('tindakaudit.temuan')
                ->cascadeOnDelete();
        });

        Schema::create('tindakaudit.rekomendasi_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('temuan_history_id')->index();
            $table->text('rekomendasi');
            $table->string('status')->index();
            $table->text('alasan')->nullable();
            $table->timestamps();
            $table->unsignedInteger('rekomendasi_id')->nullable()->index();
            $table->text('tindak_lanjut')->nullable();
            $table->string('action')->nullable()->index();

            $table->foreign('temuan_history_id')
                ->references('id')
                ->on('tindakaudit.temuan_history')
                ->cascadeOnDelete();
            $table->foreign('rekomendasi_id')
                ->references('id')
                ->on('tindakaudit.rekomendasi')
                ->nullOnDelete();
        });

        Schema::create('tindakaudit.notifikasi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode_unit', 10)->index();
            $table->string('kode_bagian')->nullable()->index();
            $table->string('action')->nullable()->index();
            $table->unsignedInteger('temuan_id')->index();
            $table->timestamps();
            $table->boolean('read')->default(false)->index();
            $table->string('message');

            $table->foreign('temuan_id')
                ->references('id')
                ->on('tindakaudit.temuan')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindakaudit.notifikasi');
        Schema::dropIfExists('tindakaudit.rekomendasi_history');
        Schema::dropIfExists('tindakaudit.rekomendasi');
        Schema::dropIfExists('tindakaudit.temuan_history');
        Schema::dropIfExists('tindakaudit.temuan');
        Schema::dropIfExists('tindakaudit.spi');
        Schema::dropIfExists('tindakaudit.bidang');
        DB::statement('DROP SCHEMA IF EXISTS tindakaudit');
    }
};
