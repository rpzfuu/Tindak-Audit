<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS hris');
        DB::statement('CREATE SCHEMA IF NOT EXISTS tindakaudit');

        Schema::create('hris.unit_usaha', function (Blueprint $table) {
            $table->id();
            $table->string('kode_unit', 10)->unique();
            $table->string('nama_unit', 50);
            $table->string('kode_grup_unit', 50)->nullable();
            $table->string('nama_grup_unit', 50)->nullable();
            $table->boolean('is_saturday_on')->default(false);
            $table->boolean('is_head_office')->default(false);
            $table->timestamps();
            $table->boolean('is_active')->default(true)->index();
        });

        Schema::create('hris.bagian', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
            $table->string('kode_unit', 10)->nullable()->index();

            $table->foreign('kode_unit')
                ->references('kode_unit')
                ->on('hris.unit_usaha')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::create('hris.sub_bagian', function (Blueprint $table) {
            $table->id();
            $table->string('bagian_code')->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();

            $table->foreign('bagian_code')
                ->references('code')
                ->on('hris.bagian')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::create('hris.karyawan', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 15)->unique();
            $table->string('nama', 50);
            $table->string('suskel', 10)->nullable();
            $table->string('ptkp', 10)->nullable();
            $table->string('kode_unit', 10)->index();
            $table->string('sub_unit', 50)->nullable();
            $table->string('egrup', 50)->nullable();
            $table->string('esubgrup', 50)->nullable();
            $table->string('jabatan', 100)->nullable();
            $table->string('jenkel', 20)->nullable();
            $table->string('pendidikan', 50)->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->date('tanggal_cuti_tahunan')->nullable();
            $table->date('tanggal_cuti_panjang')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('bod', 10)->nullable();
            $table->timestamps();
            $table->string('no_hp')->nullable();

            $table->foreign('kode_unit')
                ->references('kode_unit')
                ->on('hris.unit_usaha')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        Schema::create('hris.holiday', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->unique();
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('tindakaudit.bidang', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('tindakaudit.spi', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nik', 15)->unique();

            $table->foreign('nik')
                ->references('nik')
                ->on('hris.karyawan')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        Schema::create('tindakaudit.temuan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('created_by')->index();
            $table->text('temuan');
            $table->string('kode_unit', 10)->index();
            $table->string('status')->default('Draft')->index();
            $table->timestamps();
            $table->unsignedInteger('bidang_id')->index();
            $table->string('kode_bagian')->nullable()->index();
            $table->string('kode_subbagian')->nullable()->index();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('kode_unit')
                ->references('kode_unit')
                ->on('hris.unit_usaha')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('bidang_id')
                ->references('id')
                ->on('tindakaudit.bidang')
                ->restrictOnDelete();
            $table->foreign('kode_bagian')
                ->references('code')
                ->on('hris.bagian')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreign('kode_subbagian')
                ->references('code')
                ->on('hris.sub_bagian')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        Schema::create('tindakaudit.temuan_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('temuan_id')->index();
            $table->text('temuan');
            $table->string('status')->index();
            $table->timestamps();
            $table->unsignedBigInteger('changed_by')->index();
            $table->string('kode_unit', 10)->index();
            $table->unsignedInteger('bidang_id')->index();
            $table->string('kode_bagian')->nullable()->index();
            $table->string('keterangan')->nullable();
            $table->string('action')->nullable()->index();

            $table->foreign('temuan_id')
                ->references('id')
                ->on('tindakaudit.temuan')
                ->cascadeOnDelete();
            $table->foreign('changed_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
            $table->foreign('kode_unit')
                ->references('kode_unit')
                ->on('hris.unit_usaha')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('bidang_id')
                ->references('id')
                ->on('tindakaudit.bidang')
                ->restrictOnDelete();
            $table->foreign('kode_bagian')
                ->references('code')
                ->on('hris.bagian')
                ->cascadeOnUpdate()
                ->nullOnDelete();
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

            $table->foreign('kode_unit')
                ->references('kode_unit')
                ->on('hris.unit_usaha')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreign('kode_bagian')
                ->references('code')
                ->on('hris.bagian')
                ->cascadeOnUpdate()
                ->nullOnDelete();
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
        Schema::dropIfExists('hris.holiday');
        Schema::dropIfExists('hris.karyawan');
        Schema::dropIfExists('hris.sub_bagian');
        Schema::dropIfExists('hris.bagian');
        Schema::dropIfExists('hris.unit_usaha');
        DB::statement('DROP SCHEMA IF EXISTS tindakaudit');
        DB::statement('DROP SCHEMA IF EXISTS hris');
    }
};
