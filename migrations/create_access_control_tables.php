<?php

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
		Schema::create('roles', function (Blueprint $table) {
			$table->id();
			$table->string("name");
			$table->string("title");
			$table->json("roleables")->nullable();
			$table->timestamps();

			$table->unique(["name"]);
		});

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignIdFor(\Back2Lobby\AccessControl\Models\Role::class)->constrained();
            $table->foreignIdFor(\App\Models\User::class)->constrained();

            $table->string("roleable_type")->default("");
            $table->unsignedBigInteger("roleable_id")->default(0);

            $table->unique(["role_id", "user_id", "roleable_id", "roleable_type"]);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("title");
            $table->string("description")->nullable();
            $table->timestamps();

            $table->unique(["name"]);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignIdFor(\Back2Lobby\AccessControl\Models\Role::class)->constrained();
            $table->foreignIdFor(\Back2Lobby\AccessControl\Models\Permission::class)->constrained();
            $table->boolean("forbidden")->default(0);

            $table->unique(["role_id", "permission_id"]);
        });
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('roles');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('permission_role');
	}
};
