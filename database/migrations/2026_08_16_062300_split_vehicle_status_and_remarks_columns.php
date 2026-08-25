<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('verification_status')->default('draft')->after('status');
            $table->text('verification_remark')->nullable()->after('verification_status');
            $table->text('operator_remark')->nullable()->after('verification_remark');
            $table->text('suspension_remark')->nullable()->after('operator_remark');
        });

        DB::table('vehicles')
            ->whereIn('status', ['draft', 'pending', 'for_verification', 'needs_revision'])
            ->update([
                'verification_status' => DB::raw('status'),
                'verification_remark' => DB::raw('remarks'),
                'status' => 'inactive',
            ]);

        DB::table('vehicles')
            ->where('status', 'suspended')
            ->update([
                'verification_status' => 'verified',
                'suspension_remark' => DB::raw('remarks'),
            ]);

        DB::table('vehicles')
            ->where('status', 'active')
            ->update([
                'verification_status' => 'verified',
                'operator_remark' => DB::raw('remarks'),
            ]);

        DB::table('vehicles')
            ->where('status', 'inactive')
            ->update([
                'verification_status' => 'verified',
                'operator_remark' => DB::raw('remarks'),
            ]);

        DB::table('vehicles')
            ->where('status', 'verified')
            ->update([
                'status' => 'active',
                'verification_status' => 'verified',
                'operator_remark' => DB::raw('remarks'),
            ]);

        DB::table('vehicles')
            ->whereIn('status', ['rejected', 'invalid', 'expired'])
            ->update([
                'status' => 'inactive',
                'verification_status' => 'needs_revision',
                'verification_remark' => DB::raw('remarks'),
            ]);

        DB::table('vehicles')
            ->whereNotIn('status', ['active', 'inactive', 'suspended'])
            ->update([
                'status' => 'inactive',
                'verification_status' => 'draft',
                'operator_remark' => DB::raw('remarks'),
            ]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('remarks');
        });
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->text('remarks')->nullable()->after('status');
        });

        DB::table('vehicles')
            ->where('status', 'suspended')
            ->update([
                'remarks' => DB::raw('suspension_remark'),
            ]);

        DB::table('vehicles')
            ->whereIn('status', ['active', 'inactive'])
            ->update([
                'remarks' => DB::raw('COALESCE(operator_remark, verification_remark)'),
            ]);

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'verification_status',
                'verification_remark',
                'operator_remark',
                'suspension_remark',
            ]);
        });
    }
};
