<?php
namespace foonoo\text;


enum TagToken: string {
    case COMMENT_START_TAG = "\\\\\[\[";
    case START_TAG = '\[\[';
    case END_TAG = '\]\]';
    case ARGS_LIST = '(?<identifier>[a-zA-Z][a-zA-Z0-9_\.\-]*)(\s*)(=)(\s*)';
    case TEXT = '((?![\[\]\|])\S)+|\]|\[';
    case WHITESPACE = '[\s]+';
    case SEPARATOR = '\|';
    case DONE = "DONE";
    case STRING = "STRING";
}