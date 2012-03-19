<?php
// $Id: template.php 8021 2010-10-19 13:01:34Z sheena $

/**
 * Breadcrumb themeing
 */
function gll20_devel_breadcrumb($breadcrumb) {
  if (!empty($breadcrumb)) {
    $html = '<div class="crumbtitle">' . t('You are here:') . '</div>';
	  $html .= '<div class="breadcrumb">'. implode(' &raquo; ', $breadcrumb) .'</div>';
    return $html;
  }
}

/**
 * Page preprocessing
 */
function gll20_devel_preprocess_page(&$vars) {
  // Format the footer message
  // We do this here instead of in page.tpl.php because 
  // we need a formatted message to pass along to the
  // same theme function as the $footer in order to have
  // them nested together
  if (isset($vars['footer_message']) && strlen($vars['footer_message'])) {
    $markup = '';
    $markup .= '<div id="footer-message" class="footer-message">';
    $markup .= '<div id="footer-message-inner" class="footer-message-inner inner">';
    $markup .= $vars['footer_message'];
    $markup .= '</div><!-- /footer-message-inner -->';
    $markup .= '</div><!-- /footer-message -->';
    $vars['footer_message'] = $markup;
  }
}



/**
 * Profile preprocessing
 */
function gll20_devel_preprocess_user_profile_item(&$vars) {
  // Separate userpoints value from the edit links
  if ($vars['title'] == 'Points') { 
    $userpoints = explode(' - ', $vars['value']);
    $vars['value'] = '<span class="points">' . $userpoints[0] . '</span><span class="edit-links">' . $userpoints[1] . '</span>';
    unset($vars['title']);
  }
}

/**
 * Implementation of theme_shoutbox_post()
 */
function gll20_devel_shoutbox_post($shout, $links = array(), $alter_row_color=TRUE) {
  global $user;
  
  // Gather moderation links
  if ($links) {
    foreach ($links as $link) {
      $linkattributes = $link['linkattributes'];
      $link_html = '<img src="'. $link['img'] .'"  width="'. $link['img_width'] .'" height="'. $link['img_height'] .'" alt="'. $link['title'] .'" class="shoutbox-imglink"/>';
      $link_url = 'shout/'. $shout->shout_id .'/'. $link['action'];
      $img_links = l($link_html, $link_url, array('html' => TRUE, 'query' => array('destination' => drupal_get_path_alias($_GET['q'])))) . $img_links;
    }
  }
  
  // Generate user name with link
  $user_name = shoutbox_get_user_link($shout);

  // Generate title attribute
  $title = t('Posted !date at !time by !name', array('!date' => format_date($shout->created, 'custom', 'm/d/y'), '!time' => format_date($shout->created, 'custom', 'h:ia'), '!name' => $shout->nick));

  // Add to the shout classes
  $shout_classes = array();
  $shout_classes[] = 'shoutbox-msg';

  // Check for moderation
  if ($shout->moderate == 1) {
    $shout_classes[] = 'shoutbox-unpublished';
    $approval_message = '&nbsp;(' . t('This shout is waiting for approval by a moderator.') . ')';
  }
  
  // Check for specific user class
  $user_classes = array();
  $user_classes[] = 'shoutbox-user-name';
  if ($shout->uid == $user->uid) {
    $user_classes[] = 'shoutbox-current-user-name';
  }
  else if ($shout->uid == 0) {
    $user_classes[] = 'shoutbox-anonymous-user';  
  }
  
  // Load user image and format
  $author_picture ='';
  $shout_author =  user_load($shout->uid);
  if (!$shout_author->picture && variable_get('user_picture_default', '')) {
    $shout_author->picture = variable_get('user_picture_default', '');
  }
  if ($shout_author->picture) {
    $author_picture = theme_imagecache('user_picture_meta', $shout_author->picture, $shout_author->name, $shout_author->name);
  }
  
  // Time format
  $format = variable_get('shoutbox_time_format', 'ago');
  switch ($format) {
    case 'ago';
      $submitted =  t('!interval ago', array('!interval' => format_interval(time() - $shout->created)));
      break;
    case 'small':
    case 'medium':
    case 'large':
      $submitted = format_date($shout->created, $format);
      break;
  }
   
  // Build the post
  $post = '';
  $post .= '<div class="' . implode(' ', $shout_classes) . '" title="' . $title . '">';
  $post .= '<div class="shoutbox-admin-links">' . $img_links . '</div>';
  $post .= '<div class="shoutbox-post-info">' . $author_picture;
  $post .= '<span class="shoutbox-user-name ' . implode(' ', $user_classes) . '">'. $user_name . '</span>';
  $post .= '<span class="shoutbox-msg-time">' . $submitted . '</span>';
  $post .= '</div>';
  $post .= '<div class="shout-message">' . $shout->shout . $approval_message . '</div>';
  $post .= '</div>' . "\n";

  return $post;
}

function gll20_devel_item_list($items = array(), $title = NULL, $type = 'ul', $attributes = NULL) {
  $output = '<div class="item-list">';
  if (isset($title)) {
    $output .= '<h3>'. $title .'</h3>';
  }

  if (!empty($items)) {
    $output .= "<$type". drupal_attributes($attributes) .'>';
    $num_items = count($items);
    $c = $num_items;
    foreach ($items as $i => $item) {
    $c--;
      $attributes = array();
      $children = array();
      if (is_array($item)) {
        foreach ($item as $key => $value) {
          if ($key == 'data') {
            $data = $value;
          }
          elseif ($key == 'children') {
            $children = $value;
          }
          else {
            $attributes[$key] = $value;
          }
        }
      }
      else {
         $data = $item;
      }
      
      if (!is_numeric($i)) {
        if (count($children) > 0) {
          $data .= theme_item_list($children, NULL, $type, $attributes); // Render nested list
        }
        if ($c == $num_items - 1) {
          $attributes['class'] = empty($attributes['class']) ? 'first' : ($attributes['class'] .' first');
        }
        if ($c == 0) {
          $attributes['class'] = empty($attributes['class']) ? 'last' : ($attributes['class'] .' last');
        }
        
        $attributes['class'] .= ' ' . ($c % 2 ? 'even' : 'odd');
        $output .= '<li'. drupal_attributes($attributes) .'>'. $data ."</li>\n";
      } 
      else {
        if (count($children) > 0) {
          $data .= theme_item_list($children, NULL, $type, $attributes); // Render nested list
        }
        if ($i == 0) {
          $attributes['class'] = empty($attributes['class']) ? 'first' : ($attributes['class'] .' first');
        }
        if ($i == $num_items - 1) {
          $attributes['class'] = empty($attributes['class']) ? 'last' : ($attributes['class'] .' last');
        }
        
        $attributes['class'] .= ' ' . ($i % 2 ? 'even' : 'odd');
        $output .= '<li'. drupal_attributes($attributes) .'>'. $data ."</li>\n";
      }
    }
    
    $output .= "<$type>";
  }
  $output .= '</div>';
  return $output;
}

function gll20_devel_preprocess_block($variables) {
  $variables['template_files'][] = 'block-'.$variables['block']->region.'-'.$variables['block']->module;
  $variables['template_files'][] = 'block-'.$variables['block']->region.'-'.$variables['block']->module.'-'.$variables['block']->delta;
}

function gll20_devel_search_theme_form($form) {
	$form['search_theme_form']['#value']= t('Search...');
	$form['submit']['#type'] = 'image_button';
	$form['submit']['#src'] = drupal_get_path('theme', 'gll20_devel') . '/images/search_icon.png';
	$form['submit']['#attributes']['class'] = 'btn';
	return '<div id="search" class="container-inline">' . drupal_render($form) . '</div>';
}

function gll20_devel_commons_core_info_block() {
  $content = '';
  
  $content .= '<div id="acquia-footer-message">';

  $content .= '<a href="http://acquia.com/drupalcommons" title="' . t('Commons social business software') . '">';
  $content .= theme('image', 'profiles/drupal_commons/images/commons_droplet.png', t('Commons social business software'), t('Commons social business software'));
  $content .= '</a>';
  $content .= '<span>';
  $content .= t('A !dc Community, powered by', array('!dc' => l(t('Commons'), 'http://acquia.com/drupalcommons', array('attributes' => array('title' => t('A Commons social business software')))))) . '&nbsp;';
  $content .= l(t('Acquia'), 'http://acquia.com', array('attributes' => array('title' => t('Acquia'))));
  $content .= '</span>';
  $content .= '</div>';
  
  $content .= '<div id="fusion-footer-message">';
  $content .= t('Theme by') . '&nbsp;';
  $content .= '<a href="http://www.brightlemon.com" title="' . t('Drupal Themes by BrightLemon') . '">' . t('BrightLemon') . '</a>';
  $content .= ', ' . t('powered by') . '&nbsp;';
  $content .= '<a href="http://fusiondrupalthemes.com" title="' . t('Premium Drupal themes powered by Fusion') . '">' . t('Fusion') . '</a>.';
  $content .= '</div>';

  return $content;
}

//hide links and change page title
function gll20_devel_taxonomy_term_page($tids, $result) {
  $str_tids = arg(2);
  $terms = taxonomy_terms_parse_string($str_tids);
  $title_result = db_query(db_rewrite_sql('SELECT t.tid, t.name FROM {term_data} t WHERE t.tid IN ('. db_placeholders($terms['tids']) .')', 't', 'tid'), $terms['tids']);
  $title_tids = array(); // we rebuild the $tids-array so it only contains terms the user has access to.
  $names = array();
  while ($term = db_fetch_object($title_result)) {
    $title_tids[] = $term->tid;
    $names[] = $term->name;
  }
  $last_name = array_pop($names);
  if (count($names) == 0) {
    $title = t("Pages containing '@tag'", array('@tag' => $last_name));    
  } elseif ($terms['operator'] == "or") {
      $title = t("Pages containing '@tags or @last_tag'", array('@tags' => implode(", ", $names), '@last_tag' => $last_name));
  } else {
      $title = t("Pages containing '@tags and @last_tag'", array('@tags' => implode(", ", $names), '@last_tag' => $last_name));
  }
  drupal_set_title($title);

  drupal_add_css(drupal_get_path('module', 'taxonomy') .'/taxonomy.css');

  $output = '';

  // Only display the description if we have a single term, to avoid clutter and confusion.
  if (count($tids) == 1) {
    $term = taxonomy_get_term($tids[0]);
    $description = $term->description;

    // Check that a description is set.
    if (!empty($description)) {
      $output .= '<div class="taxonomy-term-description">';
      $output .= filter_xss_admin($description);
      $output .= '</div>';
    }
  }

  $output .= commons_connect_taxonomy_render_nodes($result);

  return $output;
}

function gll20_devel_taxonomy_render_nodes($result) {
  $output = '';
  $has_rows = FALSE;
  while ($node = db_fetch_object($result)) {
    $output .= node_view(node_load($node->nid), TRUE, FALSE, FALSE);
    $has_rows = TRUE;
  }
  if ($has_rows) {
    $output .= theme('pager', NULL, variable_get('default_nodes_main', 10), 0);
  }
  else {
    $output .= '<p>'. t('There are currently no posts in this category.') .'</p>';
  }
  return $output;
}

// call scripts
drupal_add_js(path_to_theme() . '/scripts/clear_default_searchbox_text.js', 'theme'); // call on every page since search box is displayed everywhere

/**
 * Present a node submission form.
 *
 * @ingroup themeable
 */
function gll20_devel_node_form($form) {
  $output = "\n<div class=\"node-form\">\n";

  // Admin form fields and submit buttons must be rendered first, because
  // they need to go to the bottom of the form, and so should not be part of
  // the catch-all call to drupal_render().
  $admin = '';
  if (isset($form['author'])) {
    $admin .= "    <div class=\"authored\">\n";
    $admin .= drupal_render($form['author']);
    $admin .= "    </div>\n";
  }
  if (isset($form['options'])) {
    $admin .= "    <div class=\"options\">\n";
    $admin .= drupal_render($form['options']);
    $admin .= "    </div>\n";
  }
  $buttons = drupal_render($form['buttons']);

  // Everything else gets rendered here, and is displayed before the admin form
  // field and the submit buttons.
  
  $form['group_relations']['#access'] = FALSE;
  $output .= "  <div class=\"standard\">\n";
  $output .= drupal_render($form);
  $output .= "  </div>\n";

  
  $form['group_relations']['#access'] = TRUE;
  $output .= "  <div class=\"relations\">\n";
  $output .= drupal_render($form['group_relations']);
  $output .= "  </div>\n";
  
  
  if (!empty($admin)) {
    $output .= "  <div class=\"admin\">\n";
    $output .= $admin;
    $output .= "  </div>\n";
  }
  $output .= $buttons;
  $output .= "</div>\n";

  return $output;
}

function gll20_devel_preprocess_mimemail_message(&$variables) {
  global $base_url;
  $variables['logo'] = $base_url . theme_get_setting('logo');
  $variables['front_page'] = url();
}
function gll20_devel_username($user, $link = TRUE) {
  if ($object->uid && function_exists('profile_load_profile')) {
    profile_load_profile($user);
  }
  if (isset($user->profile_name)) {
    $name = $user->profile_name . ' ' . $user->profile_last_name;
  }
  else if (isset($user->name)) {
    $name = $user->name;
  }
  else {
    $name = variable_get('anonymous', 'Anonymous');
    $link = FALSE;
  }
  if ($link && user_access('access user profiles')) {
    return l($name, 'user/' . $user->uid, array('title' => t('View user profile.')));
  }
  else {
    return check_plain($name);
  }
}

/**
 * Format a query pager.
 *
 * Menu callbacks that display paged query results should call theme('pager') to
 * retrieve a pager control so that users can view other results.
 * Format a list of nearby pages with additional query results.
 *
 * @param $tags
 *   An array of labels for the controls in the pager.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @param $quantity
 *   The number of pages in the list.
 * @return
 *   An HTML string that generates the query pager.
 *
 * @ingroup themeable
 */
function gll20_devel_pager($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
  global $pager_page_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // first is the first page listed by this pager piece (re quantity)
  $pager_first = $pager_current - $pager_middle + 1;
  // last is the last page listed by this pager piece (re quantity)
  $pager_last = $pager_current + $quantity - $pager_middle;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.

  // Prepare for generation loop.
  $i = $pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + ($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  $li_first = theme('pager_first', (isset($tags[0]) ? $tags[0] : t('« first')), $limit, $element, $parameters);
  $li_previous = theme('pager_previous', (isset($tags[1]) ? $tags[1] : t('‹ previous')), $limit, $element, 1, $parameters);
  $li_next = theme('pager_next', (isset($tags[3]) ? $tags[3] : t('next ›')), $limit, $element, 1, $parameters);
  $li_last = theme('pager_last', (isset($tags[4]) ? $tags[4] : t('last »')), $limit, $element, $parameters);

  if ($pager_total[$element] > 1) {
    if ($li_first) {
      $items[] = array(
        'class' => 'pager-first',
        'data' => $li_first,
      );
    }
    if ($li_previous) {
      $items[] = array(
        'class' => 'pager-previous',
        'data' => $li_previous,
      );
    }

    // When there is more than one page, create the pager list.
    if ($i != $pager_max) {
      if ($i > 1) {
        $items[] = array(
          'class' => 'pager-ellipsis',
          'data' => '…',
        );
      }
      $first = TRUE;
      // Now generate the actual pager piece.
      for (; $i <= $pager_last && $i <= $pager_max; $i++) {
        if ($i < $pager_current) {
          $items[] = array(
            'class' => 'pager-item pager-number',
            'data' => theme('pager_previous', $i, $limit, $element, ($pager_current - $i), $parameters),
          );
        }
        if ($i == $pager_current) {
          $items[] = array(
            'class' => 'pager-current pager-number',
            'data' => $i,
          );
        }
        if ($i > $pager_current) {
          $items[] = array( 
            'class' => 'pager-item pager-number',
            'data' => theme('pager_next', $i, $limit, $element, ($i - $pager_current), $parameters),
          );
        }
        if ($first) {
          $items[sizeof($items) - 1]['class'] .= ' pager-number-first';
	  $first = FALSE;
        }
      }
      if ($i < $pager_max) {
        $items[] = array(
          'class' => 'pager-ellipsis',
          'data' => '…',
        );
      }
    }
    $items[sizeof($items) - 1]['class'] .= ' pager-number-last';
    // End generation.
    if ($li_next) {
      $items[] = array(
        'class' => 'pager-next',
        'data' => $li_next,
      );
    }
    if ($li_last) {
      $items[] = array(
        'class' => 'pager-last',
        'data' => $li_last,
      );
    }
    return theme('item_list', $items, NULL, 'ul', array('class' => 'pager'));
  }
}
