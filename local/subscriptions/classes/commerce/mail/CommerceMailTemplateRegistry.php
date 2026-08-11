<?php

declare(strict_types=1);

namespace local_subscriptions\commerce\mail;

defined('MOODLE_INTERNAL') || die();

/**
 * Registry of transactional Commerce mail templates.
 */
final class CommerceMailTemplateRegistry {

    /**
     * @var array<string,CommerceMailTemplate>
     */
    private array $templates = [];

    /**
     * @param CommerceMailTemplate[] $templates
     */
    public function __construct(array $templates = []) {
        foreach ($templates as $template) {
            if (!$template instanceof CommerceMailTemplate) {
                throw new \coding_exception(
                    'The Commerce mail template registry received an invalid template.'
                );
            }

            $this->register($template);
        }
    }

    public function register(CommerceMailTemplate $template): void {
        $type = CommerceMailType::normalise($template->get_type());

        if (isset($this->templates[$type])) {
            throw new \coding_exception(
                'A Commerce transactional mail template is already registered for type: ' . $type
            );
        }

        $this->templates[$type] = $template;
    }

    public function has(string $type): bool {
        return isset($this->templates[CommerceMailType::normalise($type)]);
    }

    public function get(string $type): CommerceMailTemplate {
        $type = CommerceMailType::normalise($type);

        if (!isset($this->templates[$type])) {
            throw new CommerceMailTemplateNotFoundException(
                'No Commerce transactional mail template is registered for type: ' . $type
            );
        }

        return $this->templates[$type];
    }

    /**
     * @return CommerceMailTemplate[]
     */
    public function all(): array {
        return array_values($this->templates);
    }
}
