<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_ls_h', function (Blueprint $table) {
            $table->id();
            $table->string('notagihan')->unique();
            $table->date('tgl')->nullable();
            $table->text('kegiatan')->nullable();
            $table->string('penyedia')->nullable();
            $table->string('unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->decimal('jumlahbelanja', 12, 0)->default(0);
            $table->decimal('diskon', 12, 0)->default(0);
            $table->decimal('pajak', 12, 0)->default(0);
            $table->decimal('jumlahditagihkan', 12, 0)->default(0);
            $table->string('user')->nullable();
            $table->timestamps();
        });

        Schema::create('tagihan_ls_r', function (Blueprint $table) {
            $table->id();
            $table->string('notagihan');
            $table->string('akun')->nullable();
            $table->string('rincian')->nullable();
            $table->decimal('qty', 10, 0)->nullable();
            $table->string('satuan')->nullable();
            $table->decimal('harga', 12, 0)->nullable();
            $table->decimal('jumlah', 12, 0)->nullable();
            $table->string('user')->nullable();
            $table->timestamps();
        });

        Schema::create('pembayaran_ls', function (Blueprint $table) {
            $table->id();
            $table->string('nopembayaran')->unique();
            $table->date('tgl')->nullable();
            $table->string('notagihan');
            $table->string('penyedia')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('unit')->nullable();
            $table->string('user')->nullable();
            $table->decimal('sisapembayaran', 12, 0)->default(0);
            $table->decimal('nominal', 12, 0)->default(0);
            $table->string('flag', 1)->nullable();
            $table->dateTime('tgl_verif')->nullable();
            $table->string('user_verif')->nullable();
            $table->text('alasan')->nullable();
            $table->timestamps();
        });

        DB::transaction(function () {
            $tagihanLs = DB::table('tagihan_h')->where('sumberdana', 'LS')->get();

            foreach ($tagihanLs as $tagihan) {
                DB::table('tagihan_ls_h')->insert([
                    'id' => $tagihan->id,
                    'notagihan' => $tagihan->notagihan,
                    'tgl' => $tagihan->tgl,
                    'kegiatan' => $tagihan->kegiatan,
                    'penyedia' => $tagihan->penyedia,
                    'unit' => $tagihan->unit,
                    'jabatan' => $tagihan->jabatan,
                    'jumlahbelanja' => $tagihan->jumlahbelanja,
                    'diskon' => $tagihan->diskon,
                    'pajak' => $tagihan->pajak,
                    'jumlahditagihkan' => $tagihan->jumlahditagihkan,
                    'user' => $tagihan->user,
                    'created_at' => $tagihan->created_at,
                    'updated_at' => $tagihan->updated_at,
                ]);

                $rincian = DB::table('tagihan_r')->where('notagihan', $tagihan->notagihan)->get();
                foreach ($rincian as $item) {
                    DB::table('tagihan_ls_r')->insert((array) $item);
                }

                $pembayaran = DB::table('pembayaran')->where('notagihan', $tagihan->notagihan)->get();
                foreach ($pembayaran as $item) {
                    DB::table('pembayaran_ls')->insert([
                        'id' => $item->id,
                        'nopembayaran' => $item->nopembayaran,
                        'tgl' => $item->tgl,
                        'notagihan' => $item->notagihan,
                        'penyedia' => $item->penyedia,
                        'jabatan' => $item->jabatan,
                        'unit' => $item->unit,
                        'user' => $item->user,
                        'sisapembayaran' => $item->sisapembayaran,
                        'nominal' => $item->nominal,
                        'flag' => $item->flag,
                        'tgl_verif' => $item->tgl_verif,
                        'user_verif' => $item->user_verif,
                        'alasan' => $item->alasan,
                        'created_at' => $item->created_at,
                        'updated_at' => $item->updated_at,
                    ]);
                }
            }

            $nomorTagihan = $tagihanLs->pluck('notagihan');
            DB::table('pembayaran')->whereIn('notagihan', $nomorTagihan)->delete();
            DB::table('tagihan_r')->whereIn('notagihan', $nomorTagihan)->delete();
            DB::table('tagihan_h')->whereIn('notagihan', $nomorTagihan)->delete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_ls');
        Schema::dropIfExists('tagihan_ls_r');
        Schema::dropIfExists('tagihan_ls_h');
    }
};
