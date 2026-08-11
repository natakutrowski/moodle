<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\storefront\admin;

defined('MOODLE_INTERNAL') || die();

final class CommerceStorefrontVisualBuilderService {
    public function apply(array $metadata, string $language, string $action, string $type = '', string $template = ''): array {
        $language = strtolower(explode('_', str_replace('-', '_', $language))[0]);
        $storefront = is_array($metadata['storefront'] ?? null) ? $metadata['storefront'] : [];
        $storefront['locales'] = is_array($storefront['locales'] ?? null) ? $storefront['locales'] : [];
        $locale = is_array($storefront['locales'][$language] ?? null) ? $storefront['locales'][$language] : [];
        $sections = array_values(array_filter(is_array($locale['sections'] ?? null) ? $locale['sections'] : [], 'is_array'));
        [$command, $index] = $this->parse_action($action);
        if ($command === 'template') {
            $available = CommerceStorefrontPageEditor::MAX_SECTIONS - count($sections);
            if ($available > 0) {
                $templateSections = (new CommerceStorefrontComposerTemplateService())
                    ->sections($template, count($sections));
                $sections = array_merge($sections, array_slice($templateSections, 0, $available));
            }
        } else if ($command === 'add') {
            if (count($sections) >= CommerceStorefrontPageEditor::MAX_SECTIONS || !in_array($type, CommerceStorefrontPageEditor::section_types(), true)) return $metadata;
            $sections[] = ['id' => 'section-' . (count($sections)+1), 'type' => $type, 'visible' => true, 'order' => count($sections)*10, 'style' => 'default'];
        } else if ($index >= 0 && isset($sections[$index])) {
            if ($command === 'delete') array_splice($sections, $index, 1);
            else if ($command === 'duplicate' && count($sections) < CommerceStorefrontPageEditor::MAX_SECTIONS) {
                $copy=$sections[$index]; unset($copy['mediaitemid']); $copy['id']='section-'.(count($sections)+1); if (isset($copy['layout']) && is_array($copy['layout'])) { $copy['layout']['rowid']='row-'.(count($sections)+1); $copy['layout']['column']=1; } array_splice($sections,$index+1,0,[$copy]);
            } else if ($command === 'toggle') $sections[$index]['visible']=empty($sections[$index]['visible']);
            else if ($command === 'up' && $index>0) [$sections[$index-1],$sections[$index]]=[$sections[$index],$sections[$index-1]];
            else if ($command === 'down' && $index<count($sections)-1) [$sections[$index+1],$sections[$index]]=[$sections[$index],$sections[$index+1]];
            else if ($command === 'first' && $index>0) { $item=$sections[$index]; array_splice($sections,$index,1); array_unshift($sections,$item); }
            else if ($command === 'last' && $index<count($sections)-1) { $item=$sections[$index]; array_splice($sections,$index,1); $sections[]=$item; }
        }
        foreach($sections as $i=>&$section) $section['order']=$i*10;
        unset($section);
        $locale['sections']=$sections; $storefront['locales'][$language]=$locale;
        if ($language==='fr') $storefront['sections']=$sections;
        $metadata['storefront']=$storefront; return $metadata;
    }
    private function parse_action(string $action): array {
        if ($action==='add') return ['add',-1];
        if ($action==='apply_template') return ['template',-1];
        if (preg_match('/^(up|down|first|last|duplicate|delete|toggle):(\d+)$/',$action,$m)!==1) return ['',-1];
        return [$m[1],(int)$m[2]];
    }
}
