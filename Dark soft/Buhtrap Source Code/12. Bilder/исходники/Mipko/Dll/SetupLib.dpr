library SetupLib;
{$IMAGEBASE $68000000}

uses
  Windows,
  Kernel in 'Kernel.pas',
  CodeBreak in 'CodeBreak\CodeBreak.pas',
  CodeHack in 'CodeBreak\CodeHack.pas',
  LDasm in 'CodeBreak\LDasm.pas',
  Hooked in 'Hooked.pas',
  WrapFunc in '..\Shared\WrapFunc.pas',
  GlobalVar in 'GlobalVar.pas',
  Crypto in '..\Shared\Crypto.pas',
  CutSysUtils in '..\Shared\CutSysUtils.pas';

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
procedure DllMain(Reason: Integer);
begin
  case Reason of
    DLL_PROCESS_ATTACH:
       begin
       {$IFDEF DEBUG}
         DbgStr('Dll Loaded: ' + ExtractFileName(GetDllFilePath) +
                ' PID: ' + IntToStr(GetCurrentProcessId));
       {$ENDIF}
         SetKeys;
         SetHooks;
       end;
   DLL_PROCESS_DETACH:
       begin
         DelSetupFiles;
       end;
   end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
begin
  DllProc := @DllMain;
  DllProc(DLL_PROCESS_ATTACH) ;
end.
