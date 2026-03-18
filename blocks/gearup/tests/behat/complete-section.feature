@block @block_gearup @javascript
Feature: Testing completing sections
  In order for Recruits to complete challenges
  As a teacher
  I need to set them up

  Background:
    Given I activate Level Up Quest
    And the following "courses" exist:
      | fullname  | shortname | enablecompletion |
      | Course 1  | c1        | 1                |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
      | s1       | Student   | One      |
      | s2       | Student   | Two      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |
      | s1       | c1     | student        |
      | s2       | c1     | student        |
    And the following "blocks" exist:
      | blockname | contextlevel | reference |
      | gearup    | Course       | c1        |
    And I am on the "c1" "block_gearup > missions" page logged in as "t1"
    And I click on "Achievement" "link" in the "#region-main" "css_element"
    And I set the field "title" to "Completorman"
    And I select a visual
    And I press "Continue"
    And I press "New objective"
    And I click on "Complete section" clickable element
    And I set the field "Eligible section(s)" to "Specific section"
    And I set the field "Choose section" to "#2"
    And I press "Save changes"
    And I press "Continue"
    And I set the field "instructions" to "Complete section 2"
    And I press "Continue"
    And I follow "Back to the list"
    And the following "block_gearup > recruits" exist:
      | mission      | user   |
      | Completorman | s1     |

  Scenario: Recruits can complete sections with multiple activities
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1  | c1     | page     | 2       | 2          | 1              |
      | Page 2  | c1     | page     | 2       | 2          | 1              |
    And I am on the "c1" "Course" page logged in as "s1"
    And I am on the "Page 1" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"
    And I am on the "Page 2" "page activity" page
    And I am on the "c1" course page
    When I click on "Completorman" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"

  Scenario: Recruits can complete sections with mixed activities
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1  | c1     | page     | 2       | 0          | 0              |
      | Page 2  | c1     | page     | 2       | 2          | 1              |
    And I am on the "c1" "Course" page logged in as "s1"
    And I am on the "Page 1" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"
    And I am on the "Page 2" "page activity" page
    And I am on the "c1" course page
    When I click on "Completorman" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"

  Scenario: Recruits must complete all activities including unavailable
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1  | c1     | page     | 2       | 2          | 1              |
      | Page 2  | c1     | page     | 2       | 2          | 1              |
    And I am on the "Page 2" "page activity editing" page logged in as "t1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "User profile" "button"
    And I set the field "User profile field" to "Department"
    And I set the field "Value to compare against" to "Tester"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"

    And I am on the "c1" "Course" page logged in as "s1"
    And I am on the "Page 1" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "0%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"

    And I should not see "Page 2"
    And I am on the "block_gearup > Profile editing" page
    And I expand all fieldsets
    And I set the field "Department" to "Tester"
    And I click on "Update profile" "button"

    And I am on the "Page 2" "page activity" page
    And I am on the "c1" course page
    When I click on "Completorman" "link" in the "Level Up Quest" "block"
    Then I should not see "%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"

  Scenario: Recruits must not complete all unavailble activities
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1  | c1     | page     | 2       | 2          | 1              |
      | Page 2  | c1     | page     | 2       | 2          | 1              |
    And I am on the "Page 2" "page activity editing" page logged in as "t1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "User profile" "button"
    And I set the field "User profile field" to "Department"
    And I set the field "Value to compare against" to "Tester"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save and return to course" "button"
    And I am on the "Completorman" "block_gearup > mission" page
    And I click on "Edit" "button" in the definition "List of objectives"
    And I set the field "Complete when" to "Only accessible"
    And I click on "Save changes" "button" in the "Edit objective" "dialogue"

    And I am on the "c1" "Course" page logged in as "s1"
    And I am on the "Page 1" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should not see "0%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"
    And I should not see "Page 2"

  Scenario: Recruits must complete any two sections
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1a | c1     | page     | 2       | 2          | 1              |
      | Page 1b | c1     | page     | 2       | 2          | 1              |
      | Page 2a | c1     | page     | 3       | 2          | 1              |
      | Page 2b | c1     | page     | 3       | 2          | 1              |
    And I am on the "Completorman" "block_gearup > mission" page logged in as "t1"
    And I click on "Edit" "button" in the definition "List of objectives"
    And I set the field "Eligible section(s)" to "Any section"
    And I set the field "Number of sections" to "2"
    And I click on "Save changes" "button" in the "Edit objective" "dialogue"
    And I am on the "c1" "Course" page logged in as "s1"
    And I am on the "Page 1a" "page activity" page
    And I am on the "Page 1b" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"
    And I am on the "Page 2a" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
    And I click on "Close" "button" in the ".modal" "css_element"
    And I am on the "Page 2b" "page activity" page
    And I am on the "c1" course page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should not see "%" in the ".modal" "css_element"

  Scenario: Recruits with already completed sections automatically complete the objective
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1a | c1     | page     | 2       | 2          | 1              |
    And I am on the "c1" "Course" page logged in as "s2"
    And I am on the "Page 1a" "page activity" page
    And the following "block_gearup > recruits" exist:
      | mission      | user   |
      | Completorman | s2     |
    And I am on the "c1" "Course" page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should not see "%" in the ".modal" "css_element"

  Scenario: Recruits with already completed sections automatically progress the objective
    Given the following "activities" exist:
      | name    | course | activity | section | completion | completionview |
      | Page 1a | c1     | page     | 2       | 2          | 1              |
    And I am on the "Completorman" "block_gearup > mission" page logged in as "t1"
    And I click on "Edit" "button" in the definition "List of objectives"
    And I set the field "Eligible section(s)" to "Any section"
    And I set the field "Number of sections" to "2"
    And I click on "Save changes" "button" in the "Edit objective" "dialogue"
    And I am on the "c1" "Course" page logged in as "s2"
    And I am on the "Page 1a" "page activity" page
    And the following "block_gearup > recruits" exist:
      | mission      | user   |
      | Completorman | s2     |
    And I am on the "c1" "Course" page
    And I click on "Completorman" "link" in the "Level Up Quest" "block"
    And I should see "50%" in the ".modal" "css_element"
