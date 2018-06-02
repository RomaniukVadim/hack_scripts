unit MachineID;

interface

 //function MakeMachineID: string;
 function MakeMachineID_2: string;

implementation

uses
  Windows, Registry, SysUtils, Crypto, GlobalVar;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
// perform rol operation on 32-bit argument
function rol(dwArg: DWORD; bPlaces: BYTE): DWORD;
begin
  Result := (dwArg shl bPlaces) or (dwArg shr (32 - bPlaces)); 
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function myHashData(pData: Pointer; DataSize: Cardinal): DWORD;
var
  b_cr, a_cr: Byte;
  i: Integer;
begin
  Result := 0; // output result, temp hash value
  b_cr := 0;   // cr shift value

  // loop passed string
	for i := 0 to DataSize - 1 do
  begin 
    // make step's shift value, normalized to 4-byte shift (31 max)
    a_cr := PByte(Integer(pData) + i)^;
	  b_cr := (b_cr xor a_cr) and $1F;

   // xor hash with current char and rol hash, cr
	  Result := rol(Result xor a_cr,  b_cr);
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
{* Порт ф-ии из MachineID.cpp                                                                     *}
//function MakeMachineID: string;
//var
//  Reg: TRegistry;
//  pCryptKeyBuff, pWinId: Pointer;
//  iBufferSize: Longword;
//  dwExtraHashForMachineID, dwGeneratedMachineIDExtraValue,
//  dwHash1, dwHash2: DWORD;
//  flShouldMakeUniqID: Boolean;
//begin
//  Result := '';
//  flShouldMakeUniqID := False;
//  dwExtraHashForMachineID := 0;
//
//  Reg := TRegistry.Create;
//  with Reg do 
//  try
//    RootKey := HKEY_LOCAL_MACHINE;
//    Access := Access or KEY_WOW64_64KEY;
//    if OpenKeyReadOnly(DecStr(ID_REG_STR1)) then
//      begin
//        iBufferSize := 10240;
//        GetMem(pCryptKeyBuff, iBufferSize);
//        // init buffer with not-null pre-defined data to avoid clear data in registry
//        // in case of absent DigitalProductId in registry
//        FillChar(pCryptKeyBuff^, iBufferSize, 100);
//
//        try
//          if ReadBinaryData(DecStr(ID_REG_KEY1), pCryptKeyBuff^, iBufferSize) = 0 then
//             flShouldMakeUniqID := True;
//
//          dwExtraHashForMachineID := ReadInteger(DecStr(ID_REG_KEY2));
//          flShouldMakeUniqID := (dwExtraHashForMachineID = 0);
//        except
//          // Вызываемые выше ф-и могут вызвать исключение
//        end;
//     {$REGION ' Ветка, которая на выполняется ' }
//        if flShouldMakeUniqID then
//          begin
//            try
//              // first try to read existing extra value into dwGeneratedMachineIDExtraValue
//              dwGeneratedMachineIDExtraValue := ReadInteger(DecStr(ID_REG_KEY3));
//            except
//              dwGeneratedMachineIDExtraValue := 0;
//            end;
//            
//            if dwGeneratedMachineIDExtraValue = 0 then
//              begin
//                // failed to read extra value, get it from ticks
//         				// NB: we should use it ONLY if id saved successfully - otherwise to protect
//                // self from constant change of machine ids, leave dwGeneratedMachineIDExtraValue 0
//         				dwGeneratedMachineIDExtraValue := GetTickCount();
//
//                // now try to save it in registry - close read-only handle
//                CloseKey;
//
//                // try to open in write mode
//                if OpenKey(DecStr(ID_REG_STR1), False) then
//                  begin
//                    // try write key, with error-checking
//                    try
//                      WriteInteger(DecStr(ID_REG_KEY3), dwGeneratedMachineIDExtraValue);
//                    except
//                      dwGeneratedMachineIDExtraValue := 0;
//                    end;
//                  end
//                else
//                  dwGeneratedMachineIDExtraValue := 0;
//              end;
//
//            // use extra value in any case
//      			dwExtraHashForMachineID := dwExtraHashForMachineID xor dwGeneratedMachineIDExtraValue;
//          end;
//     {$ENDREGION}
//
//        // close reg key
//        CloseKey;
//
//        // make hash from WinID (16+1 bytes from pCryptKeyBuff) [HASH PART 1]
//        pWinId  := Pointer(Integer(pCryptKeyBuff) + 8);
//        dwHash1 := myHashData(pWinId, Length(StrPas(pWinId))) xor dwExtraHashForMachineID;
//
//        // and part2 using Computer name
//        iBufferSize := MAX_COMPUTERNAME_LENGTH + 1;
//        GetComputerName(pCryptKeyBuff, iBufferSize);
//
//        dwHash2 := myHashData(pCryptKeyBuff, Length(StrPas(pCryptKeyBuff)));
//
//        FreeMem(pCryptKeyBuff);
//
//        Result := IntToHex(dwHash1, 8) + IntToHex(dwHash2, 8);
//      end;
//  finally
//    Reg.Free;
//  end;
//end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function _hwsFirstVolumeModelHash: DWORD;
var
  Reg: TRegistry;
  sFirstDisk: string;
begin
  Result := 0;

  Reg := TRegistry.Create;
  with Reg do 
  try
    RootKey := HKEY_LOCAL_MACHINE;
    //Access := Access or KEY_WOW64_64KEY;
    if OpenKeyReadOnly(DecStr(REG_DSK_ENUM)) then
      begin
        sFirstDisk := ReadString(DecStr(REG_DSK_VAL));
        if sFirstDisk <> '' then
           Result := myHashData(PChar(sFirstDisk), Length(sFirstDisk));

        // close reg key
        CloseKey;
      end;
  finally
    Reg.Free;
  end;
end;

{++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++}
function MakeMachineID_2: string;
var
  pCryptKeyBuff: Pointer;
  iBufferSize: Longword;
  dwHash1, dwHash2: DWORD;
begin
  Result := '';

  iBufferSize := MAX_COMPUTERNAME_LENGTH + 1;
  GetMem(pCryptKeyBuff, iBufferSize);
  FillChar(pCryptKeyBuff^, iBufferSize, 0);
  GetComputerName(pCryptKeyBuff, iBufferSize);
  dwHash2 := myHashData(pCryptKeyBuff, Length(StrPas(pCryptKeyBuff)));

  dwHash1 := _hwsFirstVolumeModelHash;

  Result := IntToHex(dwHash1, 8) + IntToHex(dwHash2, 8);
end;


end.
