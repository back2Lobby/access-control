<?php

use App\Models\Role;
use App\Models\User;
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
		Schema::create('role_user', function (Blueprint $table) {
			$table->foreignIdFor(Role::class)->constrained();
			$table->foreignIdFor(User::class)->constrained();

			$table->string("roleable_type")->default("");
			$table->unsignedBigInteger("roleable_id")->default(0);

			$table->unique(["role_id", "user_id", "roleable_id", "roleable_type"]);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('role_user');
	}
};
