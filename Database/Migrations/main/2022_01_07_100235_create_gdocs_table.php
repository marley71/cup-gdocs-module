<?php

use Cupparis\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


class CreateGdocsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('gdocs', function(Blueprint $table)
		{
            $table->id('id');
			$table->string('gdoc_id')->nullable();
			$table->string('nome')->nullable();
			$table->text('descrizione')->nullable();
			$table->string('tipo')->default('default_doc');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('gdocs');
	}

}
