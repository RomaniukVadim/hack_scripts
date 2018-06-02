rem call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="55 8B EC 51 B9 20 00 00 00 6A 00 6A 00 49 75 F9" -key=DEADBEEF -len=FF8
rem call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="55 8B EC 83 C4 F8 84 D2 74 08 83 C4 F0 E8 1E C9" -key=DEADBEEF -len=1C4

rem call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="55 8B EC 81 C4 90 F1 FF FF 33 C9 89 8D 94 F1 FF" -key=DEADBEEF -len=1F8

rem call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="00 55 8B EC 83 C4 F8 84 D2 74 08 83 C4 F0 E8 42 90"  -key=DEADBEEF -len=1C4


rem call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="55 8B EC 33 C0 55 68 CA E4 43 00 64 FF 30 64 89 20 FF 05 C8 7C 44 00 75 4F C6 05 CC 7C 44 00 01"  -key=DEADBEEF -len=12C

call XorCrypt.exe -tgt=%~dp0Build\Adapter.exe -pat="55 8B EC 83 C4 F0 33 C0 89 45 F0 33 C0 55 68 C4" -key=DEADBEEF -len=DB

