<?php
namespace org\rhaco\flow\parts\Developer;

class Formatter{
	public function after_exec_template(&$src){
		$src = str_replace('<table>','<table class="table table-striped table-bordered table-condensed"">',$src);
	}
}