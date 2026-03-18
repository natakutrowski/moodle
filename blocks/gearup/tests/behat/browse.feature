@block @block_gearup
Feature: Testing browsing the plugin
  In order to make use of Level Up Quest
  As a teacher
  I can visit each of its pages

  Background:
    Given the following "courses" exist:
      | fullname  | shortname |
      | Course 1  | c1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | t1       | Teacher   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | t1       | c1     | editingteacher |

  Scenario: Administrators can add the block
    Given I am on the "c1" "Course" page logged in as "admin"
    And I turn editing mode on
    When I add the "Level Up Quest" block
    Then I should see "The plugin has not been activated."

  Scenario: Teachers can browse the zero states of new block
    Given I activate Level Up Quest
    And I am on the "c1" "Course" page logged in as "t1"
    And I turn editing mode on
    When I add the "Level Up Quest" block
    And I click on "Manage" "link" in the "Level Up Quest" "block"
    Then I should see "Nothing here, yet!"
    And I should see "Quest"
    And I should see "Achievement"
    And I follow "Streaks"
    And I should see "No streaks, yet!"
    And I follow "Insights"
    And I should see "Not enough data to display."
    And I follow "Library"
    And I should see "Use the library to upload your own assets"

  Scenario: Teachers can browse the pages of an achievement
    Given I activate Level Up Quest
    And the following "block_gearup > achievements" exist:
      | title              | course | instructions       |
      | Ultra Achievement  | c1     | This is what to do |
    When I am on the "Ultra Achievement" "block_gearup > achievement" page logged in as "t1"
    Then I should see "Ultra Achievement"
    And I should see "List of objectives"
    And I should see "This is what to do"
    And I follow "Insights"
    And I should see "Time to complete"
    And I follow "Recruits"
    And I should see "No recruits"
    And I follow "Recruitment"
    And I should see "List of automations"
    And I follow "Advanced"
    And I should see "Delete mission"

  Scenario: Teachers can browse the pages of a challenge
    Given I activate Level Up Quest
    And the following "block_gearup > challenges" exist:
      | title              | course | instructions       |
      | Ultra Challenge    | c1     | This is what to do |
    When I am on the "Ultra Challenge" "block_gearup > challenge" page logged in as "t1"
    Then I should see "Ultra Challenge"
    And I should see "List of objectives"
    And I should see "List of outcomes"
    And I follow "Insights"
    And I should see "Time to complete"
    And I follow "Recruits"
    And I should see "No recruits"
    And I follow "Recruitment"
    And I should see "List of automations"
    And I follow "Advanced"
    And I should see "Delete mission"

  Scenario: Teachers can browse the pages of a quest
    Given I activate Level Up Quest
    And the following "block_gearup > quests" exist:
      | title              | course | instructions       |
      | Ultra Quest        | c1     | This is what to do |
    When I am on the "Ultra Quest" "block_gearup > quest" page logged in as "t1"
    Then I should see "Ultra Quest"
    And I should see "Story line: Instructions"
    And I should see "This is what to do"
    And I should see "List of objectives"
    And I should see "Story line: Closing story"
    And I should see "List of outcomes"
    And I follow "Insights"
    And I should see "Time to complete"
    And I follow "Recruits"
    And I should see "No recruits"
    And I follow "Recruitment"
    And I should see "List of automations"
    And I follow "Advanced"
    And I should see "Delete mission"

  Scenario: Teachers can browse the pages of a streak
    Given I activate Level Up Quest
    And the following "block_gearup > streaks" exist:
      | title              | course | instructions       |
      | Ultra Streak       | c1     | This is what to do |
    When I am on the "Ultra Streak" "block_gearup > streak" page logged in as "t1"
    Then I should see "Ultra Streak"
    And I should see "This is what to do"
    And I should see "List of objectives"
    And I follow "Insights"
    And I should see "Active vs. inactive"
    And I follow "Recruits"
    And I should see "No recruits"
    And I follow "Recruitment"
    And I should see "List of automations"
    And I follow "Advanced"
    And I should see "Delete mission"
