Changelog
=========

v1.6.1
------

Bug fixes

- Awarding XP for completing a mission was throwing an error
- The restore process failed to update internal references
- Stale objectives reevaluation did not occur as intended

v1.6.0
------

New features

- Streaks! Recruits can now build streaks by completing objectives in a row
- New objective to require the recruit to reach a certain streak
- The mission tracker can be embedded in the content using a shortcode
- The order the mission types on the mission tracker can be customised
- Insights and recruits pages can be filtered by group
- Achievements can require the recruit to complete multiple objectives
- Added Streak insights to highlight activity and performance
- Missions can be archived to hide them from recruits and reduce server load

Quality of life

- The mission tracker automatically refreshes without requiring a page reload
- Managers can manually increase the streak counter of a recruit
- Many edit actions gracefully refresh the page content instead of reloading it
- The mission tracker can be hidden from the block
- The quest dialogue loading ellipsis can be skipped using the Enter or Space keys
- Improved visual appearance of back navigation buttons on management pages
- Achievement celebrations only occur in their course

Bug fixes

- Some placeholders could appear in excerpts of quest dialogues
- Challenge deadline calculations now reliably use the server time zone
- Non-repeating challenges can be reset even after they have ended
- Completion challenge objective initialisation refers to the latest instance
- Restoring in an existing course could modify existing missions
- Restoring as merge & delete was not deleting the existing missions
- Restoring user challenges could sometimes break the restore process
- Monthly challenge deadlines could extend beyond the end of the month
- Users' objective counters were not included in backups
- Users' missions iteration number and deadline were not included in backups
- Achievement celebrations can no longer happen when tabbing out of the page
- Completed challenges no longer cause the tracker to render when empty
- Quest discovery shortcode no longer renders when viewed in the mobile app

Technical changes

- Managers must have permission to access all groups in courses using groups
- Great expansion of our automated tests

Read our [release blog post](https://www.levelup.plus/blog/quest-release-1-6/) to learn more.

v1.5.0
------

New features

- New outcome to recruit a user for an achievement
- New outcome to recruit a user for a challenge
- Objectives can be displayed anywhere in a quest dialogue
- Rewards can be displayed anywhere in a quest dialogue

Quality of life

- New option to delete all the recruits across all missions
- Recruits can have their mission reset to restart from the beginning

Bug fixes

- Minor fixes and improvements

Technical changes

- Compatibility with Moodle 4.5

Read our [release blog post](https://www.levelup.plus/blog/xp-quest-release-oct-2024/) to learn more.

v1.4.0
------

New features

- New objective to take quizzes
- Objectives can lead recruits to another page
- Ability to duplicate a mission and all its settings
- View overall insights for all missions in a context
- Export overall insights data to CSV
- Export mission recruits data and their metrics to CSV
- Filter recruits by name and other identity fields
- Filter recruit missions by state (ongoing, completed, etc.)
- Remove all recruits from a mission at once
- Increased immersion by improving how dialogues are revealed

Quality of life

- Recruit objectives are now re-evaluated after an objective has been edited
- Adding emphasis on destructive actions
- Improved responsive design on recruits' views
- Page-wide actions have been added and moved to a menu
- Recruits' pages can be seen in a wide view to reveal more columns
- Design improvements to the table views found in several pages
- The user's preferred data export format is remembered
- Pages displaying a recruit include the user's avatar and identity fields
- Improved design consistency of the content displayed in the block
- Revisited the settings interface of the objective to complete profile information
- Objectives for a particular activity suggest its URL as associated page
- The missions overview page indicates the number of users recruited for each mission
- New messages in narrator dialogue are denoted from previous messages
- Removing a recruit from a mission can be done from the recruit's page

Bug fixes

- Access platform objective captures visits on the front page
- Complete section objective requires that the course supports sections
- Assigner scheduled task gracefully handles deleted missions
- Outcome to assign a quest gracefully handled deleted quests
- Handle rare case of race condition when incrementing objective
- Other fixes and improvements

Technical changes

- Raised minimum required version to Moodle 4.1

Read our [release blog post](https://www.levelup.plus/blog/quest-release-1-4/) to learn more.

v1.3.4
------

Bug fixes

- Achievement unlocked notifications could prevent navigation in rare cases
- Completed date of incomplete achievements manually marked as complete was not displayed

v1.3.3
------

Bug fixes

- Quests did not finish when learners manually closed the modal
- Text could go out of bounds for challenges with long objectives or outcomes
- Handle Moodle regression causing "Delete" buttons to disappear from modals (MDL-81339)

Quality of life

- Improved tab order of action buttons in some modals

Technical changes

- Compatibility with Moodle 4.4

v1.3.2
------

Bug fixes

- Available roles in course enrolment outcome incomplete when used sitewise

v1.3.1
------

Bug fixes

- Cohorts could not be used to recruit users in sitewide missions
- Handle PHP notice when course enrolment outcome is used sitewise
- Invalid reference to a language string in completion rate objective

v1.3.0
------

New features

- Introduced library to upload and use custom mission assets
- Page to browse all users recruited for any mission in the context
- Recruit page listing all missions a user was recruited for

Quality of life

- Top level navigation between missions, recruits and library
- Improved UX when browsing existing missions with complete redesign

v1.2.1
------

Technical changes

- Compatibility with Moodle 4.3
- Compatibility with PHP 8.2
- Removed unused get_config_structure methods from objective type

v1.2.0
------

New features

- Outcome to award coins in Motrain
- Objective to attain a certain level in Motrain

Quality of life

- Page width defaults to narrower view
- Increase size of achievements on profile page
- Improve responsiveness of charts on insights pages
- Display periodicity of challenge in its modal

Bug fixes

- Challenge outcomes could be triggered twice
- Access activity objective limited choices to activity with completion

Technical changes

- Compatibility with Moodle 4.2

v1.1.2
------

Bug fixes

- List of recruitment automations was always empty
- Include missing challenge related properties in backups
- Objectives, outcomes and assigners were not updated during restores
- Backing up a course could result in an error
- Other minor fixes and improvements

v1.1.1
------

Bug fixes

- Incorrect reference to previously renamed property

Technical changes

- Fixed incorrect plugin maturity declaration

v1.1.0
------

New features

- Extended backup support to track deeper parameters

Quality of life

- Recruits page now lists people instead of instances for challenges
- Improved insights given for challenges
- Improved backup/restore of several objectives, outcomes and assigners

Bug fixes

- Compatibility with PHP 8.1
- Fixed an incompatibility with MySQL
- Other minor fixes and improvements

v1.0.0
------

- Initial release 🎉
