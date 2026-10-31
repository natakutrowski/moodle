@block @block_gearup
Feature: Configuring mission tracker limit settings
  In order to keep the mission tracker manageable
  As an administrator
  I can limit the missions displayed to learners

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | s1       | Student   | One      |
    And the following "course enrolments" exist:
      | user | course | role    |
      | s1   | c1     | student |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |

  Scenario Outline: Administrators can limit ongoing quests and highlighted achievements independently
    Given the following "block_gearup > quests" exist:
      | title            | course | startmode |
      | Started quest 1  | c1     | 0         |
      | Started quest 2  | c1     | 0         |
      | Started quest 3  | c1     | 0         |
      | Started quest 4  | c1     | 0         |
      | Started quest 5  | c1     | 0         |
      | Started quest 6  | c1     | 0         |
    And the following "block_gearup > achievements" exist:
      | title          | course |
      | Achievement 1  | c1     |
      | Achievement 2  | c1     |
      | Achievement 3  | c1     |
      | Achievement 4  | c1     |
      | Achievement 5  | c1     |
      | Achievement 6  | c1     |
      | Achievement 7  | c1     |
      | Achievement 8  | c1     |
      | Achievement 9  | c1     |
      | Achievement 10 | c1     |
      | Achievement 11 | c1     |
    And the following "block_gearup > recruits" exist:
      | mission          | user |
      | Started quest 1  | s1   |
      | Started quest 2  | s1   |
      | Started quest 3  | s1   |
      | Started quest 4  | s1   |
      | Started quest 5  | s1   |
      | Started quest 6  | s1   |
      | Achievement 1    | s1   |
      | Achievement 2    | s1   |
      | Achievement 3    | s1   |
      | Achievement 4    | s1   |
      | Achievement 5    | s1   |
      | Achievement 6    | s1   |
      | Achievement 7    | s1   |
      | Achievement 8    | s1   |
      | Achievement 9    | s1   |
      | Achievement 10   | s1   |
      | Achievement 11   | s1   |
    And I log in as "admin"
    And I navigate to "Plugins > Blocks > Level Up Quest" in site administration
    When I set the following fields to these values:
      | Ongoing quest limit           | <ongoing>      |
      | Highlighted achievement limit | <achievements> |
    And I press "Save changes"
    And I reload the page
    And the field "Ongoing quest limit" matches value "<ongoingvalue>"
    And the field "Highlighted achievement limit" matches value "<achievementvalue>"
    And I am on the "c1" "Course" page logged in as "s1"
    Then I should see "<ongoingcount>" occurrences of "Started quest" in the "Level Up Quest" "block"
    And I should see "<achievementcount>" occurrences of "Achievement" in the "Level Up Quest" "block"

    Examples:
      | ongoing  | achievements | ongoingvalue | achievementvalue | ongoingcount | achievementcount |
      | 1        | 5            | 1            | 5                | 1            | 5                |
      | 3        | 10           | 3            | 10               | 3            | 10               |
      | 5        | 1            | 5            | 1                | 5            | 1                |
      | No limit | 3            | -1           | 3                | 6            | 3                |
      | No limit | No limit     | -1           | -1               | 6            | 11               |

  Scenario Outline: Completed achievements are highlighted when there are at most three achievements
    Given the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Nearly finished         | c1     |         |
      | Achievement in progress | c1     |         |
      | Completed achievement 1 | c1     | badge-1 |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Nearly finished         | s1   | 1     | 0.9             | 1           | 0             | 0         |
      | Achievement in progress | s1   | 1     | 0.1             | 1           | 0             | 0         |
      | Completed achievement 1 | s1   | 10    | 1               | 1           | 2             | 2         |
    And the following config values are set as admin:
      | trackermaxachievements | <limit> | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Nearly finished" in the "Level Up Quest" "block"
    And I should <remaining> "Achievement in progress" in the "Level Up Quest" "block"
    And I should see "Completed achievement 1" in the "Level Up Quest" "block"
    And there are 0 recently completed achievements in the Quest tracker
    And "View all" "link" should <more> in the "Level Up Quest" "block"

    Examples:
      | limit | remaining | more      |
      | 1     | not see   | exist     |
      | 3     | see       | not exist |
      | -1    | see       | not exist |

  Scenario Outline: A small set of completed achievements remains highlighted when none are in progress
    Given the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Completed achievement 1 | c1     | badge-1 |
      | Completed achievement 2 | c1     |         |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Completed achievement 1 | s1   | 10    | 1               | 1           | 2             | 2         |
      | Completed achievement 2 | s1   | 10    | 1               | 1           | 3             | 3         |
    And the following config values are set as admin:
      | trackermaxachievements | <limit> | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Completed achievement 1" in the "Level Up Quest" "block"
    And I should see "Completed achievement 2" in the "Level Up Quest" "block"
    And there are 0 recently completed achievements in the Quest tracker
    And "View all" "link" should not exist in the "Level Up Quest" "block"

    Examples:
      | limit |
      | 1     |
      | -1    |

  Scenario Outline: Highlight limits exclude excess achievements in progress
    Given the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Achievement in progress | c1     |         |
      | Completed achievement 1 | c1     | badge-1 |
      | Completed achievement 2 | c1     |         |
    And the following "block_gearup > recruits" exist:
      | mission                 | user |
      | Achievement in progress | s1   |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Completed achievement 1 | s1   | 10    | 1               | 1           | 2             | 2         |
      | Completed achievement 2 | s1   | 10    | 1               | 1           | 3             | 3         |
    And the following "block_gearup > achievements" exist:
      | title           | course |
      | Nearly finished | c1     |
      | Halfway there   | c1     |
      | Just started    | c1     |
    And the following "block_gearup > mission instances" exist:
      | mission         | user | state | completionratio | timestarted |
      | Nearly finished | s1   | 1     | 0.9             | 1           |
      | Halfway there   | s1   | 1     | 0.5             | 1           |
      | Just started    | s1   | 1     | 0.1             | 1           |
    And the following config values are set as admin:
      | trackermaxachievements | <limit> | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Nearly finished" in the "Level Up Quest" "block"
    And I should see "Halfway there" in the "Level Up Quest" "block"
    And I should see "Just started" in the "Level Up Quest" "block"
    And I should <remaining> "Achievement in progress" in the "Level Up Quest" "block"
    And I should not see "Completed achievement 1" in the "Level Up Quest" "block"
    And I should not see "Completed achievement 2" in the "Level Up Quest" "block"
    And there are 2 recently completed achievements in the Quest tracker
    And "View all" "link" should <more> in the "Level Up Quest" "block"

    Examples:
      | limit | remaining | more      |
      | 3     | not see   | exist     |
      | 5     | see       | not exist |
      | -1    | see       | not exist |
      | 0     | see       | not exist |

  Scenario Outline: Completed achievements are shown separately when there are more than three achievements
    Given the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Achievement in progress | c1     |         |
      | Completed achievement 1 | c1     | badge-1 |
      | Completed achievement 2 | c1     |         |
      | Completed achievement 3 | c1     | badge-1 |
    And the following "block_gearup > recruits" exist:
      | mission                 | user |
      | Achievement in progress | s1   |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Completed achievement 1 | s1   | 10    | 1               | 1           | 2             | 2         |
      | Completed achievement 2 | s1   | 10    | 1               | 1           | 3             | 3         |
      | Completed achievement 3 | s1   | 10    | 1               | 1           | 4             | 4         |
    And the following config values are set as admin:
      | trackermaxachievements | <limit> | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Achievement in progress" in the "Level Up Quest" "block"
    And I should not see "Completed achievement 1" in the "Level Up Quest" "block"
    And I should not see "Completed achievement 2" in the "Level Up Quest" "block"
    And I should not see "Completed achievement 3" in the "Level Up Quest" "block"
    And there are 3 recently completed achievements in the Quest tracker
    And "View all" "link" should not exist in the "Level Up Quest" "block"

    Examples:
      | limit |
      | 1     |
      | 5     |
      | -1    |

  Scenario Outline: Highlight limits do not change the five recently completed achievements
    Given the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Achievement in progress | c1     |         |
      | Completed achievement 1 | c1     | badge-1 |
      | Completed achievement 2 | c1     |         |
    And the following "block_gearup > recruits" exist:
      | mission                 | user |
      | Achievement in progress | s1   |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Completed achievement 1 | s1   | 10    | 1               | 1           | 2             | 2         |
      | Completed achievement 2 | s1   | 10    | 1               | 1           | 3             | 3         |
    And the following "block_gearup > achievements" exist:
      | title                   | course | visual  |
      | Completed achievement 3 | c1     | badge-1 |
      | Completed achievement 4 | c1     | badge-1 |
      | Completed achievement 5 | c1     | badge-1 |
      | Completed achievement 6 | c1     | badge-1 |
    And the following "block_gearup > mission instances" exist:
      | mission                 | user | state | completionratio | timestarted | timecompleted | timeended |
      | Completed achievement 3 | s1   | 10    | 1               | 1           | 4             | 4         |
      | Completed achievement 4 | s1   | 10    | 1               | 1           | 5             | 5         |
      | Completed achievement 5 | s1   | 10    | 1               | 1           | 6             | 6         |
      | Completed achievement 6 | s1   | 10    | 1               | 1           | 7             | 7         |
    And the following config values are set as admin:
      | trackermaxachievements | <limit> | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then I should see "Achievement in progress" in the "Level Up Quest" "block"
    And I should not see "Completed achievement" in the "Level Up Quest" "block"
    And there are 5 recently completed achievements in the Quest tracker
    And "View all" "link" should exist in the "Level Up Quest" "block"

    Examples:
      | limit |
      | 1     |
      | -1    |
