# Coursesuite API block

This is a Moodle 3.2+ block which launches Coursesuite API apps and enables direct-to-moodle publishing, creating a preconfigured single-activity scorm course in the selected category containing the scorm package.

This block is only available in the course category screens. Add the block to any (one or more) category where you want courses to be published to.

## Installation:

Install through moodle plugins interface (if enabled) or drop into the `/blocks` folder and install as other plugins.

## Configuration:

You need a Coursesuite API key and secret, which you can get from https://www.coursesuite.com, or if you are an existing user log into your account at https://www.coursesuite.ninja/ and use the API Keys area to obtain your details.

Settings for the app are under `Site Administration > Plugins > Blocks > Coursesuite API block`.

Enter the API KEY and SECRET KEY into the settings.

## Setting up & using the block

1. enable editing
2. navigate to the category where you want the block to appear (e.g. /course/index.php?categoryid=1)
3. add the block named 'Coursesuite API block'

If the API KEY has been set properly a list of apps should appear in the block. Clicking an ap will launch the app in a pop-over frame.

To send the course back to Moodle from the apps, click the Publish button on the apps' Download page. You'll be notified if the publish was successful. The course appears when you close the pop-over frame.

## Example

[![Watch the video](https://img.youtube.com/vi/zhRSFztxWkI/hqdefault.jpg)](https://youtu.be/zhRSFztxWkI)

## Licence

GPL3, same as moodle
