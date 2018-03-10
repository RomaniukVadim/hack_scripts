#!/bin/bash

REC=$(xrectsel) || exit 1

IFS='x+' read -r W H X Y <<< "$REC"

let W="$W / 6"
let H="$H / 11"

xterm -geometry $W"x"$H"+"$X"+"$Y 2>/dev/null  &
