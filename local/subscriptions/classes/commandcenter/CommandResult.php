<?php

namespace local_subscriptions\commandcenter;

defined('MOODLE_INTERNAL') || die();

final class CommandResult implements \JsonSerializable {

    private string $icon = '';
    private string $type = '';
    private string $title = '';
    private string $subtitle = '';
    private string $url = '';
    private int $score = 0;
    private array $meta = [];
    private string $group = '';
    private string $groupLabel = '';
    private string $shortcut = '';
    private string $actionLabel = '';

    public static function create(): self {
        return new self();
    }

    public static function make(
        string $icon,
        string $type,
        string $title,
        string $subtitle,
        string $url,
        int $score = 0
    ): array {
        return self::create()
            ->icon($icon)
            ->type($type)
            ->title($title)
            ->subtitle($subtitle)
            ->url($url)
            ->score($score)
            ->to_array();
    }

    public function icon(string $icon): self {
        $this->icon = $icon;
        return $this;
    }

    public function type(string $type): self {
        $this->type = $type;
        return $this;
    }

    public function title(string $title): self {
        $this->title = $title;
        return $this;
    }

    public function subtitle(string $subtitle): self {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function url(string $url): self {
        $this->url = $url;
        return $this;
    }

    public function score(int $score): self {
        $this->score = $score;
        return $this;
    }

    public function meta(string $key, $value): self {
        $this->meta[$key] = $value;
        return $this;
    }

    public function metadata(array $metadata): self {
        foreach ($metadata as $key => $value) {
            $this->meta[(string)$key] = $value;
        }

        return $this;
    }

    public function to_array(): array {
        $result = [
            'icon' => $this->icon,
            'type' => $this->type,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
            'score' => $this->score,
        ];

        if ($this->group !== '') {
            $result['group'] = $this->group;
        }

        if ($this->groupLabel !== '') {
            $result['groupLabel'] = $this->groupLabel;
        }

        if ($this->shortcut !== '') {
            $result['shortcut'] = $this->shortcut;
        }

        if ($this->actionLabel !== '') {
            $result['actionLabel'] = $this->actionLabel;
        }

        if (!empty($this->meta)) {
            $result['meta'] = $this->meta;
        }

        return $result;
    }

    public function jsonSerialize(): array {
        return $this->to_array();
    }

    public function group(string $group, string $label = ''): self {
        $this->group = $group;
        $this->groupLabel = $label;
        return $this;
    }

    public function shortcut(string $shortcut): self {
        $this->shortcut = $shortcut;
        return $this;
    }

    public function action_label(string $actionlabel): self {
        $this->actionLabel = $actionlabel;
        return $this;
    }
}