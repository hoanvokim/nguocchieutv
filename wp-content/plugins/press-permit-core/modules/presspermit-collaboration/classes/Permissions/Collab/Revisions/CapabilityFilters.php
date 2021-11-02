<?php
namespace PublishPress\Permissions\Collab\Revisions;

class CapabilityFilters
{
    function __construct()
    {
        add_filter('presspermit_has_post_cap_vars', [$this, 'has_post_cap_vars'], 10, 4);

        // @todo: confirm no longer needed
        add_action('presspermit_user_init', [$this, 'actGrantListingCaps']);

        add_filter('revisionary_can_copy', [$this, 'fltCanCopy'], 10, 5);
        add_filter('revisionary_can_submit', [$this, 'fltCanSubmit'], 10, 5);
    }

    // @todo: confirm this is no longer needed

    function actGrantListingCaps() {
        global $current_user, $revisionary;

        if (empty($revisionary->enabled_post_types)) {
            return;
        }

        $user = presspermit()->getUser();

        foreach(array_keys($revisionary->enabled_post_types) as $post_type) {
            $type_obj = get_post_type_object($post_type);

            // @todo: custom privacy caps
            foreach(['edit_published_posts', 'edit_private_posts'] as $prop) {
                if (!empty($type_obj->cap->$prop) && empty($current_user->allcaps[$type_obj->cap->$prop])) {
                    if (!empty($current_user->allcaps[$type_obj->cap->edit_posts]) || !empty($current_user->allcaps['submit_changes'])) {
                        $list_cap = str_replace('edit_', 'list_', $type_obj->cap->$prop);
                        $current_user->allcaps[$list_cap] = true;
                        $user->allcaps[$list_cap] = true;
                    }
                }
            }
        }
    }

    // @todo: move to a general module
    function fltPostAccessApplyExceptions($can_do, $operation, $post_type, $post_id, $args = []) {
        // @todo: implement PP_RESTRICTION_PRIORITY ?
        
        // @todo: implement for specific revision statuses

        $user = presspermit()->getUser();

        $op_key = "{$operation}_post";

        if (!isset($user->except[$op_key])) {
            $user->retrieveExceptions($operation, 'post');
        }

        $post_terms = [];

        // Only check for 'exclude' restrictive exceptions if not already lacking access
        if ($can_do) {
            $items = (!empty($user->except[$op_key]['post']['']['exclude'][$post_type][''])) 
            ? $user->except[$op_key]['post']['']['exclude'][$post_type]['']
            : [];

            //var_dump($user->except[$op_key]['post']['']['exclude']);

            // @todo: implement status-specific exception storage for custom workflow?

            // check for term-assigned exceptions
            if ($can_do = !in_array($post_id, $items)) {
                foreach (presspermit()->getEnabledTaxonomies(['object_type' => $post_type]) as $taxonomy) {
                    if ($term_ids = $user->getExceptionTerms($operation, 'exclude', $post_type, $taxonomy, ['status' => true, 'merge_universals' => true, 'return_term_ids' => true])) {
                        if (!isset($post_terms[$taxonomy])) {
                            $post_terms[$taxonomy] = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                        }
                        
                        if (array_intersect($term_ids, $post_terms[$taxonomy])) {
                            $can_do = false;
                            break;
                        }
                    }
                }
            }
        }

        // Only check for 'include' restrictive exceptions if not already lacking access
        if ($can_do) {
            $items = (!empty($user->except[$op_key]['post']['']['include'][$post_type][''])) 
            ? $user->except[$op_key]['post']['']['include'][$post_type]['']
            : [];

            // check for term-assigned exceptions
            if ($can_do = empty($items) || in_array($post_id, $items)) {
                foreach (presspermit()->getEnabledTaxonomies(['object_type' => $post_type]) as $taxonomy) {
                    if ($term_ids = $user->getExceptionTerms($operation, 'include', $post_type, $taxonomy, ['status' => true, 'merge_universals' => true, 'return_term_ids' => true])) {
                        if (!isset($post_terms[$taxonomy])) {
                            $post_terms[$taxonomy] = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                        }

                        if (!empty($term_ids) && !array_intersect($term_ids, $post_terms[$taxonomy])) {
                            $can_do = false;
                            break;
                        }
                    }
                }
            }
        }

        // check Enable exceptions
        if (!$can_do) {
            $items = (!empty($user->except[$op_key]['post']['']['additional'][$post_type][''])) 
            ? $user->except[$op_key]['post']['']['additional'][$post_type]['']
            : [];

            // check for term-assigned exceptions
            if (!$can_do = in_array($post_id, $items)) {
                foreach (presspermit()->getEnabledTaxonomies(['object_type' => $post_type]) as $taxonomy) {
                    if ($term_ids = $user->getExceptionTerms($operation, 'additional', $post_type, $taxonomy, ['status' => true, 'merge_universals' => true, 'return_term_ids' => true])) {
                        if (!isset($post_terms[$taxonomy])) {
                            $post_terms[$taxonomy] = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                        }
                        
                        if (array_intersect($term_ids, $post_terms[$taxonomy])) {
                            $can_do = true;
                            break;
                        }
                    }
                }
            }
        }

        return $can_do;
    }

    function fltCanCopy($can_copy, $post_id, $base_status, $revision_status, $args) {
        if (presspermit()->isAdministrator() || rvy_in_revision_workflow($post_id)) {
            return $can_submit;
        }

        $post_type = (!empty($args['type_obj'])) ? $args['type_obj']->name : get_post_field('post_type', $post_id);

        if ('draft' == $base_status) {
            $operation = 'copy';

        //} elseif ('future' == $base_status) {
            // @todo: review possible implementation for scheduled revisions
            //$operation = 'schedule'

        } else {
            return $can_copy;
        }

        return $this->fltPostAccessApplyExceptions($can_copy, $operation, $post_type, $post_id);
    }

    function fltCanSubmit($can_submit, $post_id, $new_base_status, $new_revision_status, $args) {
        if (presspermit()->isAdministrator() || !rvy_in_revision_workflow($post_id) || ('pending' != $new_base_status)) {
            return $can_submit;
        }

        $main_post_id = (!empty($args['main_post_id'])) ? $args['main_post_id'] : rvy_post_id($post_id);
        $post_type = (!empty($args['type_obj'])) ? $args['type_obj']->name : get_post_field('post_type', $post_id);

        return $this->fltPostAccessApplyExceptions($can_submit, 'revise', $post_type, $main_post_id);
    }

    private function postTypeFromCaps($caps)
    {
        foreach (get_post_types(['public' => true, 'show_ui' => true], 'object', 'or') as $post_type => $type_obj) {
            $caps = array_diff($caps, ['edit_posts', 'edit_pages']); // ignore generic caps defined for extraneous properties (assign_term, etc.) 
            if (array_intersect((array)$type_obj->cap,  $caps)) {
                return $post_type;
            }
        }

        return false;
    }

    function has_post_cap_vars($force_vars, $wp_sitecaps, $pp_reqd_caps, $vars)
    {
        $return = [];

        if (('read_post' == reset($pp_reqd_caps))) {
            if (!is_admin() && !empty($_REQUEST['post_type']) && ('revision' == $_REQUEST['post_type']) 
            && (!empty($_REQUEST['preview']) || !empty($_REQUEST['preview_id']))) {
                $return['pp_reqd_caps'] = ['edit_post'];
            }
        }

        if (('edit_post' == reset($pp_reqd_caps)) && !empty($vars['post_id'])) {
            if (rvy_in_revision_workflow($vars['post_id'])) {
                $return['return_caps'] = $wp_sitecaps;
            }
        }

        return ($return) ? array_merge((array)$force_vars, $return) : $force_vars;

        // note: CapabilityFilters::fltUserHasCap() filters return array to allowed variables before extracting
    }
}
