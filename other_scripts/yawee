#!/bin/sh

while IFS=: read ev wid; do
  case $ev in
    # window creation: center window on the screen (except docks, menus or similar)
    16) wattr o $wid || corner md $wid ;;

    # mapping requests: just set a special border for docks, menus and
    # similar. Focus other windows
    19) wattr o $wid \
      && ignw -s $wid \
      || vroum $wid ;;

    # when a window gets deleted, focus another one
    18) wattr $(pfw) || { vroum prev 2>/dev/null; groaw >/dev/null; };;

    # Focus windows when the mouse cursor enter them
    7) wattr o $wid || vroum $wid ;;
  esac
done
