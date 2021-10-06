<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2017 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_fastupload.class.php
 * \ingroup fastupload
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsFastUpload
 */
class ActionsFastUpload
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		/*
		 * IMPOSSIBLE D'UTILISER doActions ICI TANT QUE TOUTES LES PAGES D'UPLOAD N'ONT PAS UN
		 * initHooks ET UN executeHook('doActions')
		 */
	}

	/* IMPOSSIBLE D'UTILISER CE HOOK, CE SONT LES HEADERS HTML ET NON LES HEADERS HTTP QUI SONT INTERCEPTÉS */
//	function addHtmlHeader($parameters, &$user, &$action)
//	{
//		http_response_code(404);
//		$parameters['headers_sent'] = headers_sent();
//		echo '<pre>', json_encode($parameters, JSON_PRETTY_PRINT), '</pre>';
//		return 1;
//	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formattachOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf,$langs;
		$TContext = explode(':', $parameters['context']);

		if (in_array('externalaccesspage', $TContext)
			|| (in_array('adminconcatpdf', $TContext) && (float) DOL_VERSION < 12.0)
		){
			// n'est pas compatible avec le portail client
			// ni avec le module concatpdf pour les versions dolibarr inférieures à la 12.0
			return 0;
		}

		if ((float) DOL_VERSION < 6.0) {
			$this->resprints = '<link rel="stylesheet" href="'.dol_buildpath('/fastupload/css/font-awesome.min.css', 1).'">';
		}
		$jsLangs = new Translate('', $conf);
		$jsLangs->setDefaultLang($langs->defaultlang);
		$jsLangs->load('fastupload@fastupload');

		$phpContext = array(
			'hookContexts' => $TContext,
			'options' => isset($parameters['options']) ? $parameters['options'] : null,
			'conf' => array_filter(
				(array)$conf->global,
				function ($v) { return strpos($v, 'FASTUPLOAD') !== false; },
				ARRAY_FILTER_USE_KEY
			),
			'langs' => $jsLangs->tab_translate,
		);

		// pour rappel: $(<fonction>) est équivalent à $(document).ready(<fonction>)
		$this->resprints .=
			'<script type="text/javascript">'
			. '$(function() {'
			. '    FastUpload && FastUpload.overrideForm(' . json_encode($phpContext) . ')'
			. '});'
			. '</script>';

		return 0;
	}

	function showFilesList($parameters, &$object)
	{
		if (GETPOSTISSET('fastupload_ajax')) {
			ob_start();
			dol_htmloutput_events();
			$additional = ob_get_clean();
			$additional = preg_replace('/<script([^>]*)>/', '<script $1 id="fastupload_htmloutput_events">', $additional);
			echo $additional;
		}
	}
}
