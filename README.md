## Forum Keywords Report for Moodle

## Usage

The Forum Keywords Report extract keywords from all posts in a Forum activity and generate a word cloud with a table listing the weights of each keyword. It is mainly developed for analysing Chinese but appeared to also work for English. The POS (part of speech) feature follow "ICTPOS3.0" specification (https://gist.github.com/luw2007/6016931) and only works for Chinese Words.

When a keyword in the word cloud is clicked, a new page will pop-up and search the occurrences of it in the forum using the native search feature provided by Moodle.

The keywords extraction make use of jieba-php (https://github.com/fukuball/jieba-php), a Chinese text segmentation module. A few stop words were added to the jieba-php/dict/stop_words.txt file, you can also feel free to add your own. The default dictionary file is dict.big.txt (hardcoded in index.php) and cannot be changed in the settings yet.

The word cloud display make use of d3-cloud (https://github.com/jasondavies/d3-cloud) and D3.js (http://d3js.org/).

## Requirements

- Moodle 3.4+ (build 2017111300 or later), older versions may work but is not tested.
- Latest version of web browsers are recommended as most feature is rely on JavaScript.

## Installation

Unzip and copy the forumkeywords folder into your /report directory and visit your Admin Notification page to complete the installation. It will appear in the Reports folder under Course Administration.

## Author

Andy Chan <ctchan.andy@gmail.com>
GitHub: https://github.com/ctchanandy/

## License

http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
