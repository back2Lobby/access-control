<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('permission_role', function (Blueprint $table) {
			$table->foreignIdFor(Role::class)->constrained();
			$table->foreignIdFor(Permission::class)->constrained();
			$table->boolean("forbidden")->default(0);

			$table->unique(["role_id", "permission_id"]);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('permission_role');
	}
};
