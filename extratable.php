<?php
/**
 * @version 1.0 $Id: weblink.php 1627 2013-01-14 06:59:25Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @subpackage plugin.weblink
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
defined( '_JEXEC' ) or die( 'Restricted access' );

//jimport('joomla.plugin.plugin');
jimport('joomla.event.plugin');

class plgFlexicontent_fieldsExtratable extends JPlugin
{
	static $field_types = array('extratable');

	/*
	CONSTRUCTOR
	*/

	function plgFlexicontent_fieldsExtratablec( &$subject, $params )
	{
		parent::__construct( $subject, $params );
		JPlugin::loadLanguage('plg_flexicontent_fields_extratable', JPATH_ADMINISTRATOR);
		JPlugin::loadLanguage('plg_flexicontent_fields_file', JPATH_ADMINISTRATOR);
		JPluginHelper::importPlugin('flexicontent_fields', 'file' );
	}



	/*
	DISPLAY methods, item form & frontend views
	Method to create field's HTML display for item form
	*/
	function onDisplayField(&$field, &$item)
	{
	//dump($field->field_type, "onDisplayField::field_type");
	// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;

		$field->label = JText::_($field->label);

		// some parameter shortcuts
		$app       = JFactory::getApplication();
		$size      = $field->parameters->get( 'size', 30 ) ;
		$multiple  = $field->parameters->get( 'allow_multiple', 1 ) ;
		$max_values= $field->parameters->get( 'max_values', 1000 ) ;

		$lot      = $field->parameters->get( 'lot', 'lot X' ) ;
		$type      = $field->parameters->get( 'type', 'Tx' ) ;
		$prix16      = $field->parameters->get( 'prix1', '€' ) ;
		$prix7      = $field->parameters->get( 'prix1', '€' ) ;
		$surface   = $field->parameters->get( 'surface', '' ) ;
		$etage   = $field->parameters->get( 'etage', 'Nb étages' ) ;
		$balcon   = $field->parameters->get( 'balcon', '' ) ;
		$exposition   = $field->parameters->get( 'exposition', 'Exposition' ) ;
		$pdf   = $field->parameters->get( 'pdf', '' ) ;//champ pour l'url du PDF


		$required   = $field->parameters->get( 'required', 0 ) ;
		$required   = $required ? ' required' : '';

		$document  = JFactory::getDocument();
		$app				= JFactory::getApplication();


//dump($field, "FIELD");
//dump($item, "ITEM");
		// Initialise property with default value
		if ( !$field->value ) {
			$field->value = array();
			$field->value[0]['lot']  = JText::_($type, 'lot X');
			$field->value[0]['type']  = JText::_($type, 'Tx');
			$field->value[0]['prix16']  = JText::_($prix, '€');
			$field->value[0]['prix7']  = JText::_($prix, '€');
			$field->value[0]['surface'] = JText::_($surface, 'X');
			$field->value[0]['etage'] = JText::_($etage, 'Nb étages');
			$field->value[0]['balcon'] = JText::_($balcon, 'X');
			$field->value[0]['exposition'] = JText::_($exposition, 'Exposition');
			$field->value[0]['pdf'] = $pdf;
			$field->value[0] = serialize($field->value[0]);
		}

		$user = JFactory::getUser();
		$fieldname = FLEXI_J16GE ? 'custom['.$field->name.']' : $field->name;
		$elementid = FLEXI_J16GE ? 'custom_'.$field->name : $field->name;

		$js = "
			var value_counter".$field->id."=".count($field->value).";
			var maxValues".$field->id."=".$max_values.";
			var activeRow".$field->id." = '';
			
			function qfSelectFile".$field->id."(id, file)
			{
				var pdf_fileid = activeRow".$field->id."+'_pdf';
				var pdf_filename = activeRow".$field->id."+'_pdf_filename';
				pdf_fileid   = pdf_fileid.replace('_addfile','');
				pdf_filename = pdf_filename.replace('_addfile','');
				
				var pdf_fileid   = window.document.getElementById(pdf_fileid);
				var pdf_filename = window.document.getElementById(pdf_filename);
				pdf_fileid.value = id;
				pdf_filename.value = file;
				(MooTools.version>='1.2.4') ?  window.SqueezeBox.close()  :  window.document.getElementById('sbox-window').close();
			}
		";

		if ($multiple) // handle multiple records
		{
			if (!FLEXI_J16GE) $document->addScript( JURI::root(true).'/components/com_flexicontent/assets/js/sortables.js' );

			// Add the drag and drop sorting feature
			$js .= "
			window.addEvent('domready', function(){
				new Sortables($('sortables_".$field->id."'), {
					'constrain': true,
					'clone': true,
					'handle': '.fcfield-drag'
					});
				});
			";

			$js .= "
			var uniqueRowNum".$field->id."	= ".count($field->value).";  // Unique row number incremented only
			var rowCount".$field->id."	= ".count($field->value).";      // Counts existing rows to be able to limit a max number of values
			var maxVal".$field->id."		= ".$max_values.";
			
			
			function addField".$field->id."(el) {
				if((rowCount".$field->id." < maxVal".$field->id.") || (maxVal".$field->id." == 0)) {

					var thisField 	 = $(el).getPrevious().getLast();
					var thisNewField = thisField.clone();
					if (MooTools.version>='1.2.4') {
						var fx = new Fx.Morph(thisNewField, {duration: 0, transition: Fx.Transitions.linear});
					} else {
						var fx = thisNewField.effects({duration: 0, transition: Fx.Transitions.linear});
					}
					var has_select2  = jQuery(thisNewField).find('div.select2-container').length != 0;
					if (has_select2) jQuery(thisNewField).find('div.select2-container').remove();
					
					thisNewField.getElements('input.ylot').setProperty('value','lot X');
					thisNewField.getElements('input.ylot').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][lot]');
					thisNewField.getElements('input.ylot').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_lot');

					thisNewField.getElements('input.ytype').setProperty('value','Tx');
					thisNewField.getElements('input.ytype').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][type]');
					thisNewField.getElements('input.ytype').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_type');

					thisNewField.getElements('input.yprix16').setProperty('value','X€');
					thisNewField.getElements('input.yprix16').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][prix7]');
					thisNewField.getElements('input.yprix16').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_prix7');
					
					thisNewField.getElements('input.yprix7').setProperty('value','X€');
					thisNewField.getElements('input.yprix7').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][prix7]');
					thisNewField.getElements('input.yprix7').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_prix7');

					thisNewField.getElements('input.ysurface').setProperty('value','X');
					thisNewField.getElements('input.ysurface').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][surface]');
					thisNewField.getElements('input.ysurface').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_surface');

					thisNewField.getElements('select.yetage.use_select2_lib').setProperty('value','Nb étages');
					thisNewField.getElements('select.yetage.use_select2_lib').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][etage]');
					thisNewField.getElements('select.yetage.use_select2_lib').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_etage');

					thisNewField.getElements('input.ybalcon').setProperty('value','X');
					thisNewField.getElements('input.ybalcon').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][balcon]');
					thisNewField.getElements('input.ybalcon').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_balcon');

					thisNewField.getElements('select.yexposition.use_select2_lib').setProperty('value','Exposition');
					thisNewField.getElements('select.yexposition.use_select2_lib').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][exposition]');
					thisNewField.getElements('select.yexposition.use_select2_lib').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_exposition');

					thisNewField.getElements('input.ypdf').setProperty('value','');
					thisNewField.getElements('input.ypdf').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][pdf]');
					thisNewField.getElements('input.ypdf').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_pdf');
					thisNewField.getElements('input.ypdf_name').setProperty('value','');
					thisNewField.getElements('input.ypdf_name').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][pdf_filename]');
					thisNewField.getElements('input.ypdf_name').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_pdf_filename');
					

					thisNewField.getElements('a.addfile_".$field->id."').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id."+'_addfile');
					thisNewField.getElements('a.addfile_".$field->id."').setProperty('href','".
					JURI::base(true).'/index.php?option=com_flexicontent&view=fileselement&tmpl=component&index="+uniqueRowNum'.$field->id.'+"&field='.$field->id.'&itemid='.$item->id.'&autoselect=1&items=0&filter_uploader='.$user->id.'&'.(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken())."=1');
					if (has_select2)  jQuery(thisNewField).find('select.use_select2_lib').select2();
					jQuery(thisNewField).insertAfter( jQuery(thisField) );

					SqueezeBox.initialize({});
					if (MooTools.version>='1.2.4') {

						SqueezeBox.assign($$('a.addfile_".$field->id."'), {
							parse: 'rel'
						});
					} else {

						$$('a.addfile_".$field->id."').each(function(el) {
							el.addEvent('click', function(e) {
								new Event(e).stop();
								SqueezeBox.fromElement(el);
							});
						});
					}

					new Sortables($('sortables_".$field->id."'), {
						'constrain': true,
						'clone': true,
						'handle': '.fcfield-drag'
					});

					fx.start({ 'opacity': 1 }).chain(function(){
						this.setOptions({duration: 600});
						this.start({ 'opacity': 0 });
						})
						.chain(function(){
							this.setOptions({duration: 300});
							this.start({ 'opacity': 1 });
						});

					rowCount".$field->id."++;       // incremented / decremented
					uniqueRowNum".$field->id."++;   // incremented only
				}
			}

			function deleteField".$field->id."(el)
			{
				if(rowCount".$field->id." <= 1) return;
				var field	= $(el);
				var row		= field.getParent();
				if (MooTools.version>='1.2.4') {
					var fx = new Fx.Morph(row, {duration: 300, transition: Fx.Transitions.linear});
				} else {
					var fx = row.effects({duration: 300, transition: Fx.Transitions.linear});
				}

				fx.start({
					'height': 0,
					'opacity': 0
				}).chain(function(){
					(MooTools.version>='1.2.4')  ?  row.destroy()  :  row.remove();
				});
				rowCount".$field->id."--;
			}
			";

			$css = '
			#sortables_'.$field->id.' table.admintable td {padding-top:0px; paddin-bottom:0px;}
			#flexicontent input.fcfield_textval {
  				height: 26px !important;
  				line-height: 26px !important;
  				min-width: 0 !important;
  				overflow: hidden !important;
			}
			#sortables_'.$field->id.' { margin: 0px; padding: 0px; list-style: none; white-space: nowrap; }
			#sortables_'.$field->id.' li {
				clear: both;
				display: block;
				list-style: none;
				height: auto;
				position: relative;
			}
			#sortables_'.$field->id.' li.sortabledisabled {
				background : transparent url(components/com_flexicontent/assets/images/move3.png) no-repeat 0px 1px;
			}
			#sortables_'.$field->id.' li input { cursor: text;}
			#sortables_'.$field->id.' li input.inline_style_published   { font-family:tahoma!important; font-style:italic!important; color:#444!important; font-style:tahona; }
			#sortables_'.$field->id.' li input.inline_style_unpublished { background: #ffffff; color:gray; border-width:0px; text-decoration:line-through; }
			#add'.$field->name.' { margin-top: 5px; clear: both; display:block; }
			#sortables_'.$field->id.' li .admintable { text-align: left; }
			#sortables_'.$field->id.' li:only-child span.fcfield-drag, #sortables_'.$field->id.' li:only-child input.fcfield-button { display:none; }
			#sortables_'.$field->id.' label.label, #sortables_'.$field->id.' input.ytype, #sortables_'.$field->id.' input.yprix, #sortables_'.$field->id.' input.fcfield-button {
				float: none!important;
				display: inline-block!important;
			}
			';

			$remove_button = '<input class="fcfield-button" type="button" value="'.JText::_( 'FLEXI_REMOVE_VALUE' ).'" onclick="deleteField'.$field->id.'(this);" />';
			$move2 	= '<span class="fcfield-drag">'.JHTML::image ( JURI::base().'components/com_flexicontent/assets/images/move2.png', JText::_( 'FLEXI_CLICK_TO_DRAG' ) ) .'</span>';

		} else {
			$remove_button = '';
			$move2 = '';
			$js = '';
			$css = '';
		}

		if ($js)  $document->addScriptDeclaration($js);
		if ($css) $document->addStyleDeclaration($css);
		JHTML::_('behavior.modal', 'a.addfile_'.$field->id);//code pour la popup du file manager

		static $select2_added = false;
	 	if ( !$select2_added )
	  	{
			$select2_added = true;
			flexicontent_html::loadFramework('select2');//code pour ajouter le javascript select2list (on rajoute use_select2_lib dans la class de la liste)
		}


		// ***********************************
		// Get data of files via a single call
		// ***********************************
		
		$file_ids = array();
		$unserialized_values = array();
		foreach ($field->value as $value)
		{


			if ( empty($value) ) continue;
			
			// Compatibility for old unserialized values e.g. 'file' field
			if ( @unserialize($value)!== false || $value === 'b:0;' ) {
				$value = unserialize($value);
			} else {
				$value = array( 'lot' => '', 'type' => '', 'prix16' => '','prix7' => '', 'surface' => '', 'etage' => '', 'balcon'=>'', 'exposition' => '', 'pdf' => $value);
			}
			$file_ids[] = $value['pdf'];
			$unserialized_values[] = $value;
		}
		$files_data = $this->getFileData( $file_ids, $published=false );
		//print_r($files_data);
		
		
		$field->html = array();
		$n = 0;
		foreach ($unserialized_values as $value)
		{
			$fieldname = FLEXI_J16GE ? 'custom['.$field->name.']['.$n.']' : $field->name.'['.$n.']';
//dump($fieldname , "new fieldname");
			$elementid = FLEXI_J16GE ? 'custom_'.$field->name.'_'.$n : $field->name.'_'.$n;
//dump($value , "value ".$n);
			
			$lot = '
				<label class="label">Lot:</label>
				<input class="ylot'.$required.' fcfield_textval inputbox" name="'.$fieldname.'[lot]" id="'.$elementid.'_lot" type="text" size="5" value="'.$value['lot'].'" />
			';

			$type = '
				<label class="label">Type:</label>
				<input class="ytype'.$required.' fcfield_textval inputbox" name="'.$fieldname.'[type]" id="'.$elementid.'_type" type="text" size="5" value="'.$value['type'].'" />
			';

			$prix16 = '
				<label class="label">prix 16,6%:</label>
				<input class="yprix16 fcfield_textval inputbox" name="'.$fieldname.'[prix16]" id="'.$elementid.'_prix16" type="text" size="8" value="'.$value['prix16'].'" />
			';
			
			$prix7 = '
				<label class="label">prix 7%:</label>
				<input class="yprix7 fcfield_textval inputbox" name="'.$fieldname.'[prix7]" id="'.$elementid.'_prix7" type="text" size="8" value="'.$value['prix7'].'" />
			';

			$surface= '
				<label class="label">Surface:</label>
				<input class="ysurface fcfield_textval inputbox" name="'.$fieldname.'[surface]" id="'.$elementid.'_surface" type="text" size="2" value="'.$value['surface'].'" />
			';

                // generate state drop down list

                        $listarrays = array(
                                                array('RDC','RDC'),
												array('1','1'),
                                                array('2','2'),
                                                array('3','3'),
                                                array('4','4'),
                                                array('5','5'),
                                                array('6','6'),
                                                array('7','7'),
                                                array('8','8'),
                                                array('9','9'),
                                                array('10','10'));
                $options = array();
                $options[] = JHTML::_('select.option', '', 'Nb Etage');
                $i = 1;

                $display = "";
                foreach ($listarrays as $listarray) {
                        $options[] = JHTML::_('select.option', $listarray[0], $listarray[1]);
                        if ($field->value[0] == $listarray[0]) {
                                $display = $listarray[1];
                        }
                        $i++;
                }
			$etage= '<label class="label" >Etages:</label>'.JHTML::_('select.genericlist', $options, $fieldname.'[etage]', " class='yetage use_select2_lib'", 'value', 'text', $value['etage'], $elementid.'_etage');
			$balcon= '
				<label class="label" >Balcon/Terrase :</label>
				<input class="ybalcon fcfield_textval inputbox" name="'.$fieldname.'[balcon]" id="'.$elementid.'_balcon"  type="text" size="15" value="'.$value['balcon'].'" />
			';
			// generate state drop down list

                        $listarrays2 = array(
                                                array('nord','nord'),
												array('nord-ouest','nord-ouest'),
												array('nord-est','nord-est'),
                                                array('sud','sud'),
												array('sud-ouest','sud-ouest'),
												array('sud-est','sud-est'),
                                                array('est','est'),
                                                array('ouest','ouest'));

                $options = array();
                $options[] = JHTML::_('select.option', '', 'Exposition');
                $i = 1;

                $display = "";
                foreach ($listarrays2 as $listarray2) {
                        $options[] = JHTML::_('select.option', $listarray2[0], $listarray2[1]);
                        if ($field->value[0] == $listarray2[0]) {
                                $display = $listarray2[1];
                        }
                        $i++;
                }

			$exposition= '<label class="label" >Exposition:</label>'.JHTML::_('select.genericlist', $options, $fieldname.'[exposition]', " class='yexposition  use_select2_lib'", 'value', 'text', $value['exposition'], $elementid.'_exposition');

			/*début du code pour ajouter le bouton pour lancer le filemanager de FLEXIcontent*/
			$user = JFactory::getUser();
			$autoselect = 1;
			$linkfsel = JURI::base(true).'/index.php?option=com_flexicontent&view=fileselement&tmpl=component&index='.$i.'&field='.$field->id.'&itemid='.$item->id.'&autoselect='.$autoselect.'&items=0&filter_uploader='.$user->id.'&'.(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken()).'=1';

			$file_id = (int) (@ $value['pdf'] );
			if ( !empty($files_data[$file_id]) )
			{
				$file_data = $files_data[$file_id];
				$filename  = $file_data->filename;
			} else {
				$fileid = '';
				$filename = '';
			}
			
			$pdf= '
				<label class="label" >PDF:</label>
				<input class="ypdf fcfield_textval inputbox" name="'.$fieldname.'[pdf]" id="'.$elementid.'_pdf"  type="hidden" size="2" value="'.$file_id.'" />
				<input class="ypdf_name fcfield_textval inputbox" name="'.$fieldname.'[pdf_filename]" id="'.$elementid.'_pdf_filename"  type="text" size="15" value="'.$filename.'" />
			';
			$pdf .="<div class=\"fcfield-button-add\" style=\"display:inline-block;\">
			<div class=\"blank\">
			<a class=\"addfile_".$field->id."\" onclick='activeRow".$field->id."=this.id.replace(\"_addfile\",\"\");' id='".$elementid."_addfile' title=\"".JText::_( 'FLEXI_PDF' )."\" href=\"".$linkfsel."\" rel=\"{handler: 'iframe', size: {x:(MooTools.version>='1.2.4' ? window.getSize().x : window.getSize().size.x)-100, y: (MooTools.version>='1.2.4' ? window.getSize().y : window.getSize().size.y)-100}}\">".JText::_( 'FLEXI_PDF' )."</a>
			</div></div>
			";

			// import des plugins pour étendre le champ Extratable (offres, ...)
			JPluginHelper::importPlugin('amallia');
			$context = "plg_flexicontent_fields.extratable";
			$plugincontents="";
			JDispatcher::getInstance()->trigger('onExtratablePrepareForm', array($context, $item, $field, $n, &$plugincontents, $this->params->toArray()));


			//generation du code HTML pour un groupe de champ coté admin
			$field->html[] = '
			<div style="border: 1px solid #ccc; border-radius:5px;padding:5px;margin-bottom:5px;margin-top:5px;background:#F4F4F4;display:table;">
				<div style="float:left;">'.$lot.'</div>
				<div style="float:left;">'.$type.'</div>
				<div style="float:left;">'.$prix16.'</div>
				<div style="float:left;">'.$prix7.'</div>
				<div style="float:left;">'.$surface.'</div>
				<div style="float:left;">'.$etage.'</div>
				<div style="float:left;">'.$balcon.'</div>
				<div style="float:left;">'.$exposition.'</div>
				<div style="float:left;">'.$pdf.'</div>
				
				<div style="float:left;">'.$move2.'</div>
				<div style="float:left;">'.$remove_button.'</div>
				<div style="float:left;">'.$plugincontents.'</div>
				</div>

				';

			$n++;
			if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
		}

		if ($multiple) { // handle multiple records
			$li_list = '<li>'. implode('</li><li>', $field->html) .'</li>';
			$field->html = '<ul class="fcfield-sortables" id="sortables_'.$field->id.'">' .$li_list. '</ul>';
			$field->html .= '<input type="button" class="fcfield-addvalue" style="clear:both;" onclick="addField'.$field->id.'(this);" value="'.JText::_( 'FLEXI_ADD_VALUE' ).'" />';
		} else {  // handle single values
			$field->html = $field->html[0];
		}
		$field->html .= '<input id="'.$field->name.'" class="'.$required.'" style="display:none;" name="__fcfld_valcnt__['.$field->name.']" value="'.($n ? $n : '').'">';
	}


	// Method to create field's HTML display for frontend views
	function onDisplayFieldValue(&$field, $item, $values=null, $prop='display')
	{
		// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;

		$field->label = JText::_($field->label);

		// Get field values
		$values = $values ? $values : $field->value;
		if ( empty($values) ) { $field->{$prop} = ''; return; }

		// Prefix - Suffix - Separator parameters, replacing other field values if found
		$remove_space = $field->parameters->get( 'remove_space', 0 ) ;
		$pretext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'pretext', '' ), 'pretext' );
		$posttext		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'posttext', '' ), 'posttext' );
		$separatorf	= $field->parameters->get( 'separatorf', 1 ) ;
		$opentag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'opentag', '' ), 'opentag' );
		$closetag		= FlexicontentFields::replaceFieldValue( $field, $item, $field->parameters->get( 'closetag', '' ), 'closetag' );
		

		if($pretext)  { $pretext  = $remove_space ? $pretext : $pretext . ' '; }
		if($posttext) { $posttext = $remove_space ? $posttext : ' ' . $posttext; }

		// some parameter shortcuts
		$target = $field->parameters->get( 'targetblank', 0 ) ? ' target="_blank"' : '';
		$usetitle      = $field->parameters->get( 'use_title', 0 ) ;
		$title_usage   = $field->parameters->get( 'title_usage', 0 ) ;
		$default_title = ($title_usage == 2)  ?  JText::_($field->parameters->get( 'default_value_title', '' )) : '';
		
		
		$precount   = $field->parameters->get( 'precount', 'T1 :' ) ;
		$postcount  = $field->parameters->get( 'postcount', 'appartements' ) ;
		
		$lienplan    = $field->parameters->get( 'lienplan', 'plan PDF' ) ;
	

		switch($separatorf)
		{
			case 0:
			$separatorf = '&nbsp;';
			break;

			case 1:
			$separatorf = '<br />';
			break;

			case 2:
			$separatorf = '&nbsp;|&nbsp;';
			break;

			case 3:
			$separatorf = ',&nbsp;';
			break;

			case 4:
			$separatorf = $closetag . $opentag;
			break;

			case 5:
			$separatorf = '';
			break;

			default:
			$separatorf = '&nbsp;';
			break;
		}

		// ***********************************
		// Get data of files via a single call
		// ***********************************
		

		$file_ids = array();
		$unserialized_values = array();
		foreach ($values as $value)
		{
			if ( empty($value) ) continue;
			
			// Compatibility for old unserialized values e.g. 'file' field
			if ( @unserialize($value)!== false || $value === 'b:0;' ) {
				$value = unserialize($value);
			} else {
				$value = array( 'lot' => '','type' => '', 'prix16' => '', 'prix7' => '', 'surface' => '', 'etage' => '', 'balcon'=>'', 'exposition' => '', 'pdf' => $value);
			}
			$file_ids[] = $value['pdf'];
			$unserialized_values[] = $value;
		}
		$files_data = $this->getFileData( $file_ids, $published=false );
		
		// ***************************************************
		// Get user access level (these are multiple for J2.5)
		// ***************************************************
		$user = JFactory::getUser();
		if (FLEXI_J16GE) $aid_arr = $user->getAuthorisedViewLevels();
		else             $aid = (int) $user->get('aid');
		$public_acclevel = !FLEXI_J16GE ? 0 : 1;
		$regaccess_only_msg = 'register to download';
		$noaccess_msg = 'no download access';
		
		
		// ************************************************************************
		// initialise display property, and loop through values creating their HTML
		// ************************************************************************
		
		$field->{$prop} = array();
		$n = 0;
		foreach ($unserialized_values as $value)
		{
			$file_id = (int) $value['pdf'];
			//if ( empty($files_data[$file_id]) ) continue; //si pdf vide

			// *****************************
			// Check user access on the file
			// *****************************
			
			$authorized = true;
			$is_public  = true;
			if ( !empty($file_data->access) ) {
				if (FLEXI_J16GE) {
					$authorized = in_array($files_data[$file_id]->access,$aid_arr);
					$is_public  = in_array($public_acclevel,$aid_arr);
				} else {
					$authorized = $files_data[$file_id]->access <= $aid;
					$is_public  = $files_data[$file_id]->access <= $public_acclevel;
				}
			}
			
			// *****************************************************
			// Create the download link -or- set a no access message
			// *****************************************************
			
			if ( !$authorized && $is_public ) {
				$dl_text = $regaccess_only_msg;
			} else if ( !$authorized ) {
				$dl_text = $noaccess_msg;  // maybe create a parameter for no access message ?
			} else if ( empty($files_data[$file_id]) ){
				$dl_text = '';
			} else {
				$dl_link = JRoute::_( 'index.php?option=com_flexicontent&id='. $file_id .'&cid='.$field->item_id.'&fid='.$field->id.'&task=download' );
				$dl_text = '<a class="btn" href="'.$dl_link.'">'.$lienplan.'</a>';
			}




			// ****************************
			// Create the HTML of the value
			// ****************************

			$field->{$prop}[] =
				'<tr>'.
					$pretext.''.@ $value['lot'].''.$posttext.''.
					$pretext.''.@ $value['type'].''.$posttext.''.
					$pretext.''.@ $value['prix16'].''.$posttext.''.
					$pretext.''.@ $value['prix7'].''.$posttext.''.
					$pretext.''.@ $value['surface'].' m2 '.$posttext.''.
					$pretext.''.@ $value['etage'].''.$posttext.''.
					$pretext.''.@ $value['balcon']. ' m2 '.$posttext.''.
					$pretext.''.@ $value['exposition'].''.$posttext.''.

					$pretext.$dl_text.$posttext.
				'</tr> ';
			
			$n++;
		}
		
		
		// *******************
		// Get total of values
		// *******************
		$totalLignes = $n;
		$displayTotalLignes = $precount.' '. $totalLignes.' '.$postcount;
		
		
		// **********************************************************
		// Display field HTML, applying separator and open/close tags
		// **********************************************************


		if(count($field->{$prop})) {
			$field->{$prop} = implode($separatorf, $field->{$prop});
			$field->{$prop} = $displayTotalLignes . $opentag . $field->{$prop} . $closetag;
		} else {
			$field->{$prop} = '';
		}
	}



	/*
	METHODS HANDLING before & after saving / deleting field events
	Method to handle field's values before they are saved into the DB
	*/
	function onBeforeSaveField( &$field, &$post, &$file, &$item )
	{
		// execute the code only if the field type match the plugin type
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !is_array($post) && !strlen($post) ) return;


		$is_importcsv = JRequest::getVar('task') == 'importcsv';


		// Make sure posted data is an array
		$post = !is_array($post) ? array($post) : $post;


		// Reformat the posted data
		$newpost = array();
		$new = 0;
		foreach ($post as $n => $v)
		{
			// support for basic CSV import / export,  TO BE REMOVED added to the 'store' function of the model
			if ( $is_importcsv && !is_array($post[$n]) ) {
				if ( @unserialize($post[$n])!== false || $post[$n] === 'b:0;' ) {  // support for exported serialized data)
					$post[$n] = unserialize($post[$n]);
				} else {
					$post[$n] = array('lot' => '','type' => '', 'prix16' => '','prix7' => '', 'surface' => '', 'etage' => '', 'balcon' => '', 'exposition'=>'' ,'pdf'=> $post[$n]);
				}
			}

			$newpost[$new] = $post[$n];
			$new++;
		}
		// Serialize multi-property data before storing them into the DB
		foreach($post as $i => $v) {
			$post[$i] = serialize($v);
		}
	}



	// Method to take any actions/cleanups needed after field's values are saved into the DB
	function onAfterSaveField( &$field, &$post, &$file, &$item ) {
	}


	// Method called just before the item is deleted to remove custom item data related to the field
	function onBeforeDeleteField(&$field, &$item) {
	}



	/*
	CATEGORY/SEARCH FILTERING METHODS
	Method to display a search filter for the advanced search view
	*/
	function onAdvSearchDisplayFilter(&$filter, $value='', $formName='searchForm')
	{
		if ( !in_array($filter->field_type, self::$field_types) ) return;

		$filter->parameters->set( 'display_filter_as_s', 1 );  // Only supports a basic filter of single text search input
		FlexicontentFields::createFilter($filter, $value, $formName);
	}

 	// Method to get the active filter result (an array of item ids matching field filter, or subquery returning item ids)
	// This is for search view
	function getFilteredSearch(&$field, $value)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;

		$field->parameters->set( 'display_filter_as_s', 1 );  // Only supports a basic filter of single text search input
		return FlexicontentFields::getFilteredSearch($field, $value, $return_sql=true);
	}



	/*
	SEARCH / INDEXING METHODS
	Method to create (insert) advanced search index DB records for the field values
	*/
	function onIndexAdvSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !$field->isadvsearch && !$field->isadvfilter ) return;

		FlexicontentFields::onIndexAdvSearch($field, $post, $item, $required_properties=array('link','title'), $search_properties=array('title'), $properties_spacer=' ', $filter_func=null);
		return true;
	}


	// Method to create basic search index (added as the property field->search)
	function onIndexSearch(&$field, &$post, &$item)
	{
		if ( !in_array($field->field_type, self::$field_types) ) return;
		if ( !$field->issearch ) return;

		FlexicontentFields::onIndexSearch($field, $post, $item, $required_properties=array('link','title'), $search_properties=array('title'), $properties_spacer=' ', $filter_func=null);
		return true;
	}	
	

	// **********************
	// VARIOUS HELPER METHODS
	// **********************
	
	function getFileData( $value, $published=1, $extra_select='' )
	{
		// Find which file data are already cached, and if no new file ids to query, then return cached only data
		static $cached_data = array();
		$return_data = array();
		$new_ids = array();
		$values = is_array($value) ? $value : array($value);
		foreach ($values as $file_id) {
			$f = (int)$file_id;
			if ( !isset($cached_data[$f]) && $f)
				$new_ids[] = $f;
		}
		
		// Get file data not retrieved already
		if ( count($new_ids) )
		{
			// Only query files that are not already cached
			$db = JFactory::getDBO();
			$query = 'SELECT * '. $extra_select //filename, altname, description, ext, id'
					. ' FROM #__flexicontent_files'
					. ' WHERE id IN ('. implode(',', $new_ids) . ')'
					. ($published ? '  AND published = 1' : '')
					;
			$db->setQuery($query);
			$new_data = $db->loadObjectList('id');

			if ($new_data) foreach($new_data as $file_id => $file_data) {
				$cached_data[$file_id] = $file_data;
			}
		}
		
		// Finally get file data in correct order
		foreach($values as $file_id) {
			$f = (int)$file_id;
			if ( isset($cached_data[$f]) && $f)
				$return_data[$file_id] = $cached_data[$f];
		}

		return !is_array($value) ? @$return_data[(int)$value] : $return_data;
	}

}
