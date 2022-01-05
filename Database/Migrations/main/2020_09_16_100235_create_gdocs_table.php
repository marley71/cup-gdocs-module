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
            			$table->increments('id');
			$table->string('gdoc_id')->nullable();
			$table->string('nome')->nullable();
			$table->text('descrizione')->nullable();
			$table->enum('tipo',['affitto','affitto_cumulativo'])->default('affitto');
			$table->integer('dominio_id')->unsigned()->nullable()->index();
			$table->foreign('dominio_id')->references('id')->on('domini')->onDelete('cascade')->onUpdate('cascade');
			
			

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
