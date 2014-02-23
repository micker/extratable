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
		$app				= JFactory::getApplication();
		$size      = $field->parameters->get( 'size', 30 ) ;
		$multiple  = $field->parameters->get( 'allow_multiple', 1 ) ;
		$maxval    = $field->parameters->get( 'max_values', 0 ) ;

		$type      = $field->parameters->get( 'type', 'Tx - lot X' ) ;
		$prix      = $field->parameters->get( 'prix', 'à partir de €' ) ;
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
			$field->value[0]['type']  = JText::_($type, 'tx - lot X');
			$field->value[0]['prix']  = JText::_($prix, 'à partir de X€');
			$field->value[0]['surface'] = JText::_($surface, 'X');
			$field->value[0]['etage'] = JText::_($etage, 'Nb étages');
			$field->value[0]['balcon'] = JText::_($balcon, 'X');
			$field->value[0]['exposition'] = JText::_($exposition, 'Exposition');
			$field->value[0]['pdf'] = JText::_($pdf, 'pdf');
			$field->value[0] = serialize($field->value[0]);
		}

		$js = "";

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

			$fieldname = FLEXI_J16GE ? 'custom['.$field->name.']' : $field->name;
			$elementid = FLEXI_J16GE ? 'custom_'.$field->name : $field->name;

			$js .= "
			var uniqueRowNum".$field->id."	= ".count($field->value).";  // Unique row number incremented only
			var rowCount".$field->id."	= ".count($field->value).";      // Counts existing rows to be able to limit a max number of values
			var maxVal".$field->id."		= ".$maxval.";

			
			
			function addField".$field->id."(el) {
				if((rowCount".$field->id." < maxVal".$field->id.") || (maxVal".$field->id." == 0)) {

					var thisField 	 = $(el).getPrevious().getLast();
					var thisNewField = thisField.clone();
					if (MooTools.version>='1.2.4') {
						var fx = new Fx.Morph(thisNewField, {duration: 0, transition: Fx.Transitions.linear});
					} else {
						var fx = thisNewField.effects({duration: 0, transition: Fx.Transitions.linear});
					}
// CORRECT
					thisNewField.getElements('input.ytype').setProperty('id','".$elementid."_'+uniqueRowNum".$field->id.");
					thisNewField.getElements('input.ytype').setProperty('value','Tx - lot X');
					thisNewField.getElements('input.ytype').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][type]');

					thisNewField.getElements('input.yprix').setProperty('value','à partir de X€');
					thisNewField.getElements('input.yprix').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][prix]');

					thisNewField.getElements('input.ysurface').setProperty('value','X');
					thisNewField.getElements('input.ysurface').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][surface]');

					thisNewField.getElements('select.yetage.use_select2_lib').setProperty('value','Nb étages');
					thisNewField.getElements('select.yetage.use_select2_lib').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][etage]');

					thisNewField.getElements('input.ybalcon').setProperty('value','X');
					thisNewField.getElements('input.ybalcon').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][balcon]');

					thisNewField.getElements('select.yexposition.use_select2_lib').setProperty('value','Exposition');
					thisNewField.getElements('select.yexposition.use_select2_lib').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][exposition]');

					thisNewField.getElements('input.ypdf').setProperty('value','pdf');
					thisNewField.getElements('input.ypdf').setProperty('name','".$fieldname."['+uniqueRowNum".$field->id."+'][pdf]');

					
				

					// Set hits to zero for new row value
					if (MooTools.version>='1.2.4') {
						thisNewField.getElements('span span').set('html','0');
					} else {
						thisNewField.getElements('span span').setHTML('0');
					}

					jQuery(thisNewField).insertAfter( jQuery(thisField) );

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
		JHTML::_('behavior.modal', 'a.modal_'.$field->id);//code pour la popup du file manager

		static $select2_added = false;
	 	if ( !$select2_added )
	  	{
			$select2_added = true;
			flexicontent_html::loadFramework('select2');//code pour ajouter le javascript select2list (on rajoute use_select2_lib dans la class de la liste)
		}
		$field->html = array();
		$n = 0;
		foreach ($field->value as $value) {
//dump('custom['.$field->name.']['.$n.']' , "new field");
			if ( @unserialize($value)!== false || $value === 'b:0;' ) {
				$value = unserialize($value);
			} else {
				$value = array('type' => '', 'prix' => '', 'surface' => '', 'etage' => '', 'balcon'=>'', 'exposition' => '', 'pdf' => '');
			}
			$fieldname = FLEXI_J16GE ? 'custom['.$field->name.']['.$n.']' : $field->name.'['.$n.']';
//dump($fieldname , "new fieldname");
			$elementid = FLEXI_J16GE ? 'custom_'.$field->name : $field->name;
//dump($value , "value ".$n);


			$type = '
				<label class="label">Type:</label>
				<input class="ytype'.$required.' fcfield_textval inputbox" name="'.$fieldname.'[type]" id="'.$elementid.'_'.$n.'" type="text" size="15" value="'.$value['type'].'" />
			';

			$prix = '
				<label class="label">prix:</label>
				<input class="yprix fcfield_textval inputbox" name="'.$fieldname.'[prix]" type="text" size="20" value="'.$value['prix'].'" />
			';

			$surface= '
				<label class="label">Surface:</label>
				<input class="ysurface fcfield_textval inputbox" name="'.$fieldname.'[surface]" type="text" size="2" value="'.$value['surface'].'" />
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

			$etage= JHTML::_('select.genericlist', $options, $fieldname.'[etage]', " class='yetage use_select2_lib'", 'value', 'text', $value['etage']);
			$balcon= '
				<label class="label">Balcon/Terrase :</label>
				<input class="ybalcon fcfield_textval inputbox" name="'.$fieldname.'[balcon]" type="text" size="2" value="'.$value['balcon'].'" />
			';
			// generate state drop down list

                        $listarrays2 = array(
                                                array('nord','nord'),
                                                array('sud','sud'),
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

			$exposition= JHTML::_('select.genericlist', $options, $fieldname.'[exposition]', " class='yexposition  use_select2_lib'", 'value', 'text', $value['exposition']);

			/*début du code pour ajouter le bouton pour lancer le filemanager de FLEXIcontent*/
			$user = JFactory::getUser();
			$autoselect = 1;
			$linkfsel = JURI::base(true).'/index.php?option=com_flexicontent&amp;view=fileselement&amp;tmpl=component&amp;index='.$i.'&amp;field='.$field->id.'&amp;itemid='.$item->id.'&amp;autoselect='.$autoselect.'&amp;items=0&amp;filter_uploader='.$user->id.'&amp;'.(FLEXI_J30GE ? JSession::getFormToken() : JUtility::getToken()).'=1';
			$files_data = !empty($field->value);
			$pdf ="<div class=\"fcfield-button-add\">
			<div class=\"blank\">
			<a class=\"modal_".$field->id."\" title=\"".JText::_( 'FLEXI_ADD_FILE' )."\" href=\"".$linkfsel."\" rel=\"{handler: 'iframe', size: {x:(MooTools.version>='1.2.4' ? window.getSize().x : window.getSize().size.x)-100, y: (MooTools.version>='1.2.4' ? window.getSize().y : window.getSize().size.y)-100}}\">".JText::_( 'FLEXI_ADD_FILE' )."</a>
			</div>
		</div>
		<input id='".$field->name."' class='".$required."' style='display:none;' name='__fcfld_valcnt__[".$field->name."]' value='".$value["pdf"]."'> ";

			// import des plugins pour étendre le champ Extratable (offres, ...)
			JPluginHelper::importPlugin('amallia');
			$context = "plg_flexicontent_fields.extratable";
			$plugincontents="";
			JDispatcher::getInstance()->trigger('onExtratablePrepareForm', array($context, $item, $field, $n, &$plugincontents, $this->params->toArray()));

			//generation du code HTML pour un groupe de champ
			$field->html[] = '
				'.$type.'
				'.$prix.'
				'.$surface.'
				'.$etage.'
				'.$balcon.'
				'.$exposition.'
				'.$pdf.'
				'.$move2.'
				'.$remove_button.'
				';

			$n++;
			if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
		}

		if ($multiple) { // handle multiple records
			$field->html = '<li>'. implode('</li><li>', $field->html) .'</li>';
			$field->html = '<ul class="fcfield-sortables" id="sortables_'.$field->id.'">' .$field->html. '</ul>';
			$field->html .= '<input type="button" class="fcfield-addvalue" style="clear:both;" onclick="addField'.$field->id.'(this);" value="'.JText::_( 'FLEXI_ADD_VALUE' ).'" />';
		} else {  // handle single values
			$field->html = $field->html[0];
		}
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
		
		
		$precount      = $field->parameters->get( 'precount', 'T1 :' ) ;
		$postcount    = $field->parameters->get( 'postcount', 'appartements' ) ;
		
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

		//comptage total des lignes
		$totalLignes= count($values);
		$field->{$prop}[] = $totalLignes;
		$displayTotalLignes = $precount.' '. $totalLignes.' '.$postcount;

		// initialise property
		$field->{$prop} = array();
		$n = 0;
		foreach ($values as $value)
		{
			if ( empty($value) ) continue;

			// Compatibility for old unserialized values
			$value = (@unserialize($value)!== false || $value === 'b:0;') ? unserialize($value) : $value;
			if ( is_array($value) ) {
				$type = $value['type'];
				$prix = $value['prix'];
				$surface = $value['surface'];
				$etage = $value['etage'];
				$balcon = $value['balcon'];
				$exposition = $value['exposition'];
				$pdf = $value['pdf'];
			} else {
				$type = '';
				$prix = '';
				$surface = '';
				$etage = '';
				$balcon = '';
				$exposition = '';
				$pdf = '';
			}

			// If not using property or property is empty, then use default property value
			// NOTE: default property values have been cleared, if (propertyname_usage != 2)


				$field->{$prop}[] =  '<tr>'.$pretext.'' .$type.''.$posttext. ''.$pretext.''. $prix . ''.$posttext.''.$pretext.''. $surface . ' m2 '.$posttext.''.$pretext.'' .$etage. ''.$posttext.''.$pretext.'' .$balcon. 'm2 '.$posttext.''.$pretext.'' .$exposition.''.$posttext.' '.$pretext.'<a class="btn" href="'.$pdf.'" target="_blank">'.$lienplan.'</a>'.$posttext.'</tr> ';
			$n++;
		}

		// Apply seperator and open/close tags
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
					$post[$n] = array('type' => $post[$n], 'prix' => '', 'surface' => '', 'etage' => '', 'balcon' => '', 'exposition'=>'' ,'pdf'=>'');
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
}
