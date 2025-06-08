# Level Up Quest Availability (availability_gearup)

Moodle plugin to control access to course content based on Level Up Quest.

Lock or unlock access to activities and sections based on the status of
progression of learners in their quests and achievements.

## Requirements

- Level Up Quest
- Moodle 3.11 or greater.

## How to use

1. Install this plugin
2. Add a 'Level Up Quest' restriction under 'Restrict access' to activities or sections
3. Trust other plugins to unlock access.

## Installation

### Git

Do not clone the `master` branch in a production environment. Branches are reserved for development and should be considered unstable. Instead, please refer to tags to identify the latest stable versions.

```
git tag -l | sort -Vr | head -1
git checkout <tag here>
```

### Zip upload

If you have configured Moodle to allow plugin installation from the user interface, and you received a zip of the plugin, follow the following steps. If not, refer to the manual process.

1. Visit the _Install plugins_ admin page (Site administration > Plugins > Install plugins)
2. Drag & drop the plugin in the _Zip package_ area
3. Click _Install plugin from the ZIP file_ and follow the process

### Manual process

1. Place the content of this plugin in the folder `availability/condition/gearup`.
2. Visit your admin's _Notifications_ page (Site administration > Notifications)
3. Follow the upgrade process

## License

Licensed under the [GNU GPL License](http://www.gnu.org/copyleft/gpl.html)
