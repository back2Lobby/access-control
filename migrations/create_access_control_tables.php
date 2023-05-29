<?php

use Back2Lobby\AccessControl\Stores\SessionStore;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{

		$userTableName = SessionStore::getInstance()->getAuthUserTable();
		$userTableNameSingular = Str::singular($userTableName);

		Schema::create('roles', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('title');
			$table->json('roleables')->nullable();
			$table->timestamps();

			$table->unique(['name']);
		});

		Schema::create('assigned_roles', function (Blueprint $table) use ($userTableName, $userTableNameSingular) {

			$table->unsignedBigInteger('role_id');
			$table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

			$table->unsignedBigInteger($userTableNameSingular . '_id');
			$table->foreign($userTableNameSingular . '_id')->references('id')->on($userTableName)->onDelete('cascade');

			$table->string('roleable_type')->default('');
			$table->unsignedBigInteger('roleable_id')->default(0);

			$table->unique(['role_id', $userTableNameSingular . '_id', 'roleable_id', 'roleable_type']);
		});

		Schema::create('permissions', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->string('title');
			$table->string('description')->nullable();
			$table->timestamps();

			$table->unique(['name']);
		});

		Schema::create('assigned_permissions', function (Blueprint $table) {
			$table->unsignedBigInteger('role_id');
			$table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

			$table->unsignedBigInteger('permission_id');
			$table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');

			$table->boolean('forbidden')->default(0);

			$table->unique(['role_id', 'permission_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('roles');
		Schema::dropIfExists('assigned_roles');
		Schema::dropIfExists('permissions');
		Schema::dropIfExists('assigned_permissions');
	}
};
