
Var STR_HAYSTACK
Var STR_NEEDLE
Var STR_CONTAINS_VAR_1
Var STR_CONTAINS_VAR_2
Var STR_CONTAINS_VAR_3
Var STR_CONTAINS_VAR_4
;Var STR_RETURN_VAR ; Avast detect
 
Function StrContains
    Exch $STR_NEEDLE
    Exch 1
    Exch $STR_HAYSTACK
	;StrCpy $STR_RETURN_VAR ""
        StrCpy $9 ""
	StrCpy $STR_CONTAINS_VAR_1 -1
	StrLen $STR_CONTAINS_VAR_2 $STR_NEEDLE
	StrLen $STR_CONTAINS_VAR_4 $STR_HAYSTACK
	Loop:
	    IntOp $STR_CONTAINS_VAR_1 $STR_CONTAINS_VAR_1 + 1
	    StrCpy $STR_CONTAINS_VAR_3 $STR_HAYSTACK $STR_CONTAINS_VAR_2 $STR_CONTAINS_VAR_1
	    StrCmp $STR_CONTAINS_VAR_3 $STR_NEEDLE Found
	    StrCmp $STR_CONTAINS_VAR_1 $STR_CONTAINS_VAR_4 Done
	    Goto Loop
    Found:
	;StrCpy $STR_RETURN_VAR $STR_NEEDLE
        StrCpy $9 $STR_NEEDLE
	Goto Done
Done:
    Pop $STR_NEEDLE
    ;Exch $STR_RETURN_VAR   
    Exch $9
FunctionEnd

!macro _StrContainsConstructor OUT NEEDLE HAYSTACK
    Push $9
    Push `${HAYSTACK}`
    Push `${NEEDLE}`
    Call StrContains
    Pop `${OUT}`
    Pop $9
!macroend

!define StrContains '!insertmacro "_StrContainsConstructor"'

Function FindTrap
    Push $0
    Push $1

    ; > Без комментариев

    System::Call "kernel32::IsDebuggerPresent()i.r0"
    ${If} $0 = 0

	    ; > Процессы мониторов и отладчиков

	    ${FindProcess} 'wireshark.exe,regmon.exe,filemon.exe,procmon.exe,vboxservice.exe,vmtoolsd.exe,ollydbg.exe,windbg.exe,syserapp.exe,x96_dbg.exe,x32_dbg.exe,x64_dbg.exe' $0
	    ${If} $0 = 0

		    ; > Модули мониторов и отладчиков

		    System::Call "kernel32::GetModuleHandle(t 'dbghelp.dll')i.r0"
		    ${If} $0 = 0
			System::Call "kernel32::GetModuleHandle(t 'pstorec.dll')i.r0"
			${If} $0 = 0
			    System::Call "kernel32::GetModuleHandle(t 'vmcheck.dll')i.r0"
			    ${If} $0 = 0
				System::Call "kernel32::GetModuleHandle(t 'wpespy.dll')i.r0"
				${If} $0 = 0
				    System::Call "kernel32::GetModuleHandle(t 'sbiedll.dll')i.r0"
				    ${If} $0 = 0
					System::Call "kernel32::GetModuleHandle(t 'api_log.dll')i.r0"
					${If} $0 = 0
					    System::Call "kernel32::GetModuleHandle(t 'dir_watch.dll')i.r0"
					    ${If} $0 = 0

						    ; > Драйверы отладчиков ядра

						    ${IfNot} ${FileExists} '$SYSDIR\drivers\sice.sys'
							${IfNot} ${FileExists} '$SYSDIR\drivers\ntice.sys'
							    ${IfNot} ${FileExists} '$SYSDIR\drivers\syser.sys'
								${IfNot} ${FileExists} '$SYSDIR\drivers\winice.sys'

									; > Путь к исполняемому файлу при нахождении в различных песочницах

									System::Call "kernel32::GetModuleFileName(i 0, t .r1, i 1024)i.r0"
									${If} $0 != 0
									    ${StrContains} $0 "c:\t.exe" $1
									    ${If} $0 == ""
										${StrContains} $0 "c:\myapp.exe" $1
										${If} $0 == ""
										    ${StrContains} $0 "c:\self.exe" $1
										    ${If} $0 == ""
											${StrContains} $0 "c:\file.exe" $1
											${If} $0 == ""
											    ${StrContains} $0 "c:\analyzer\scan\" $1
											    ${If} $0 == ""
												${StrContains} $0 "c:\test\" $1
												${If} $0 == ""
												    ${StrContains} $0 "c:\ohcbulyb.exe" $1
												    ${If} $0 == ""

													    ; > Различные небезопасные проверки

													    Call SomeUnsafeActions
													    Pop $0
													    ${If} $0 = 0

														    ; > Виртуальные машины

														    ExpandEnvStrings $R0 %COMSPEC%
														    nsExec::ExecToStack '"$R0" /C Systeminfo'
														    Pop $0
														    Pop $0
														    StrCpy $1 $0 1024
														    ${StrContains} $0 "VirtualBox" $1
														    ${If} $0 == ""
															${StrContains} $0 "Virtual Machine" $1
															${If} $0 == ""
															    ${StrContains} $0 "VMware" $1
															    ${If} $0 == ""
																GoTo NoTrap
															    ${EndIf}
															${EndIf}
														    ${EndIf}

														    ; ^

													    ${EndIf}

													    ; ^

												    ${EndIf}
												${EndIf}
											    ${EndIf}
											${EndIf}
										    ${EndIf}
										${EndIf}
									    ${EndIf}
									${EndIf}

									; ^

								${EndIf}
							    ${EndIf}
							${EndIf}
						    ${EndIf}

						    ; ^

					    ${EndIf}
					${EndIf}
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${EndIf}
		    ${EndIf}

		    ; ^

	    ${EndIf}

	    ; ^

    ${EndIf}

    ; ^

    Pop $1
    Pop $0
    Push 1
    GoTo Return
NoTrap:
    Pop $1
    Pop $0
    Push 0
Return: 
FunctionEnd

!define stPROCESS_MEMORY_COUNTERS '(&i4, &i4, &i4, &i4, &i4, &i4, &i4, &i4, &i4, &i4) i'

Function SomeUnsafeActions
    Push $0
    Push $1

    ; > Попытка закрыть несуществующие дескрипторы

    System::Call "ntdll::ZwClose(i 0)i.r0"
    ${If} $0 != 0
	System::Call "kernel32::CloseHandle(i 0)i.r0"
	${If} $0 = 0
	    System::Call "kernel32::CloseHandle(i 0x12345678)i.r0"
	    ${If} $0 = 0

		    ; > Нежелательные родительские процессы

		    System::Call "kernel32::GetCurrentProcessId()i.r0"
		    ${If} $0 != 0
			${FindParentProcessName} $0 $1
			${If} $1 != 0
			    ${StrContains} $0 "perl.exe" $1
			    ${If} $0 == ""
				${StrContains} $0 "python.exe" $1
				${If} $0 == ""
				    ${StrContains} $0 "autoit3.exe" $1
				    ${If} $0 == ""
					GoTo continue
				    ${EndIf}
				${EndIf}
			    ${EndIf}
			${Else}
			continue:

			    ; > Лимит на размер используемой процессом памяти в момент запуска

			    System::Call "*(${stPROCESS_MEMORY_COUNTERS})p.r1"
			    ${If} $1 != 0
				System::Call "psapi::GetProcessMemoryInfo(i 0xFFFFFFFF, p r1, i 40)i.r0"
				${If} $0 != 0
				    System::Call "*$1${stPROCESS_MEMORY_COUNTERS} i (,,,,,,,,.r0,)"
				    ${If} $0 < 0x1000000
					System::Free $1
					Goto NoTrap
				    ${EndIf}
				${EndIf}
				System::Free $1
			    ${EndIf}

			    ; ^

			${EndIf}
		    ${EndIf}

		    ; ^

	    ${EndIf}
	${EndIf}
    ${EndIf}

    ; ^

    Pop $1
    Pop $0
    Push 1
    GoTo Return
NoTrap:
    Pop $1
    Pop $0
    Push 0
Return:  
FunctionEnd
