@block @block_gearup
Feature: Configuring ongoing quest order on the mission tracker
  In order to show recruits the most useful quests first
  As an administrator
  I can order ongoing quests by relevance or alphabetically

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
    And the following "block_gearup > quests" exist:
      | title          | course |
      | Apple quest    | c1     |
      | Birch quest    | c1     |
      | Cedar quest    | c1     |
      | Willow quest   | c1     |
    And the following "block_gearup > mission instances" exist:
      | mission      | user | state | completionratio | timestarted | timecompleted |
      | Apple quest  | s1   | 1     | 0               | 30          | 0             |
      | Birch quest  | s1   | 2     | 1               | 20          | 50            |
      | Cedar quest  | s1   | 1     | 0               | 40          | 0             |
      | Willow quest | s1   | 2     | 1               | 10          | 60            |

  Scenario: Ongoing quests are ordered by relevance by default
    Given the following config values are set as admin:
      | trackermaxongoingquests | -1 | block_gearup |
    When I am on the "c1" "Course" page logged in as "s1"
    Then "Willow quest" "link" should appear before "Birch quest" "link" in the "Level Up Quest" "block"
    And "Birch quest" "link" should appear before "Cedar quest" "link" in the "Level Up Quest" "block"
    And "Cedar quest" "link" should appear before "Apple quest" "link" in the "Level Up Quest" "block"

  Scenario: Administrators can change the order of quests included within the tracker limit
    Given I am on the "c1" "Course" page logged in as "admin"
    And I navigate to "Plugins > Blocks > Level Up Quest" in site administration
    When I set the following fields to these values:
      | Ongoing quest order | Alphabetical |
      | Ongoing quest limit | 3            |
    And I press "Save changes"
    And I am on the "c1" "Course" page logged in as "s1"
    Then "Apple quest" "link" should appear before "Birch quest" "link" in the "Level Up Quest" "block"
    And "Birch quest" "link" should appear before "Cedar quest" "link" in the "Level Up Quest" "block"
    And I should not see "Willow quest" in the "Level Up Quest" "block"
    And I am on the "c1" "Course" page logged in as "admin"
    And I navigate to "Plugins > Blocks > Level Up Quest" in site administration
    And I set the field "Ongoing quest order" to "Most relevant"
    And I press "Save changes"
    And I am on the "c1" "Course" page logged in as "s1"
    And "Willow quest" "link" should appear before "Birch quest" "link" in the "Level Up Quest" "block"
    And "Birch quest" "link" should appear before "Cedar quest" "link" in the "Level Up Quest" "block"
    And I should not see "Apple quest" in the "Level Up Quest" "block"
