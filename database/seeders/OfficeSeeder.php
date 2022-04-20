<?php

namespace Database\Seeders;

use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Данные
     * 
     * @var array
     */
    protected $offices = "eyJpdiI6ImNNK0V2UlprbzNDS1NTRXRGb1pnTmc9PSIsInZhbHVlIjoiaTNSMlBQbnFDT294dXd5elZ0MGV3M3JZbG9GU3c5VHZrNXBUOWoyUkUwYTg0bXcyRldHWkRoa0VnUUZZUGhSNTZPai9KVFFHc0hMS3YxQzN3eUJVdHF1T1RSbEh3bWM1TVpkVGQzNk5XUzFBZGNKR3BteDNyWTJtNThYNDdaOXl4WnhiWmxPQlJaU3Jtd0RCdU91VlEvUkl4clV3cUJHRXF5aFlSNVJUclc2Wk9sajkwc2tLMG9tWUV3ckJGejI4L1lRSXMrelRDamZZUzRBaWQyVFEvSTAwZkNRWmxMNlRGc1NtcGQveGRVM2YyeEVSRUdrNFFpMmRyeTVwNnhHTzhvNHYwZ0JlWm1HM2Q4a3ZOVU9xaGZKNmlaZEZCYkkxcThkbk10V2Rzd1ViUy9WZkUzWEtkaGNuallFSWRJTkY0NXlmREtnK1RnZzBsbzRLRVRqTlhWcU9NYjVFYUx3cGFDa1cvcTN4dGNxM0VsemNud0tFdUVWNi94R3J6Ym1CdW9uTXVTcHBXRDhObzFIaFdiejBkNEVWaFdXTUFhdW45bDRydGFlTndrcmNCcXE4ZEhnZXhzc0NsRWp5eEl3ak15dnNCSWtZUkp3RGdZWUdHWmhwZVFTRkNqSGNTRGh6cld3aGdmRm94bitSMXZrOEVFWDM3eVNyTzMxVmRzem11b2dlN2Ziby8ydWwrUTh2QWdVS0htWi9wQnZFZFc2bzBvT2Mrck1RUEJMYUZWcDlYR0tsbnZmRTJSdWUrZktnZVM1WlIrNlRyMW5wZk1xWTgrZGQvejZKTVVvdURXTVd5eitQbFdqMFpCMkxFS1FZVHVsbTRWZHNzekNTQy8xOUZpN0tiOHpONGpqTjUrYngwbmM3dXhaVVkyY1JyTEY2NmxlOGI5QjU2elplSFBkR1NXZ09wUnJnc3ViNzQyZzh2cTJteGJ4LzdXZUFQb1ZFcnVTSGZRWXpLL3pOejNuRUdidmptQ01UYWd4V3hXc3lXRjl6SlBJbEthZUsrNkRzM3FPaWIrY3U4Q2YzaTZJTFBEVFJoVzJSQWhsMHJHb3FEUHc3VUxKTEZIREVKcE53YTZ1VjNOR3hFbGRia09hS1RRNk0xUWYyZ1RJQ3JRVG8xaTFuK2Q0YzJTVjdYVi91RVhqbi9vYXZ5bjZwUndxSC9UMmdIYU9xemtGcVY0SGY2b3VQYXU3dTlyM3RjR0VzUnlyMlNTVktYSHVLWXEyN3J4VThYM2tPdWYvM1REbHNOVUZ4Z0xTVExyU2FHL0RaMXdUL2lHd3o4a0sxcjJaeSs0RjczUnZkTTZlSHlwWWJxc1hlY0JYWFoxK0ZkWUo2MFZQcmE3MXBmZ3BzUTVSeEo0aGxyeXdsOFUxUnk3U0V6aUhvVE13REFBalRNTjFxT1A2bHMrbXEwa3NJOHFCWjhTci9tUHZDdkw1N0cxenREY1BPRUpJdHFCeFZnSWJkT2NOUGJOZGx3TFkwSlJtZ3JpeTAxNHM3M2FwbGxLRi9tY0YxMlBHbDEzN0p0cVIwdHRsVlhmZUQ0WU9LeW5KRGVzMGJvMm1sNExHYU9ZNTVnWlg1Qmw1UEVBZ3NjZ21jRDUyU253cHJuVXd3aDZSYTBwRFlJcUtDOWUwUDAvdGg5Rk5sOStMeFBtc3A1eStSN0dhNE9STTZQQ3RUNkkwN1pQTmY4c3dDMERWbjE0SFNCT0dIZWgxVjRWSmQ5aWlYNWFONzF5TjFEbHAybWh3SjhkWjY1bkY2K2JRc3ZWUWtqWkJLUEJTVXVYU2IzRmpZRFRkaDFzaXpSOVRESmVTb2trb3NvRTNGT3pHZHl6V2dacW5Yd1JMK3ptc0RXZUNSQVhhbFFaT1d6VkI3YXN6QmRlcHBnZiticU5VRC9FdnJ3Q29TMnJoVDQ4NnhXbDZjQ0pHdzJ6Um9lR1UvSmxoaWkraVFQSWkyM3dhQlA0bUl1WFUwMENobkVTS2dHOTFkNzVZME1Ud08rNFRlaEt5MEM1YXdFbytHaHh2ZG8yZlEvRDZZWnRub2UzNXpuT0JrNCtwNzBkclJ6WS9CczNYM0p5aWl2NWdWUWJLWkFCd1VibTZCYlk0Nk1PaHJVTy9hbG45UC9zWkdmZWU2YmxZQVlvMTJNeUtlV0hoWFV2cFZ2NzA3bnkzS1N5MnZ0S01YQmZoZU9xUkZlell4RjZFZnFJVnU5NHl4RHBOd3YwKzJsdjBtZ1lQS0lFQ0pKZStuN1JtUlpJaWdPR2FxR1l4NHJPUnRLcHdQalUvMmQyb3ptM1NBczcvYUxBOTdVSDZMdlJ6bEh4YjRsNDJiNEx0RW9hbzhJcGtKSXdOVmlwS0hEUjZPV29DMnFkbW9WSVd4bFhYdmU0TGp3MmdiQlNsTGdyMG5ZeXpZeDVaMHo3cEF3dnl2dGI4MFVxRG1IMW9ZNTdRcHZaQ09xdDNlYmpOYnJiaENGaitIcEgxbmFUemVVYXdUZm9MS3lzR1RVNDJZL0E5cnIrVmRER2plZWVrREEvV2s0R3hRdGJ2OXZqV2dhV2QrK2hPdStleVNicjdvTW0vRkZNSUFpOW9QSnBnd2ZYb1VZc0YvVklZbWRFVUNkZ0c0YVp3MXJiWkhyOEt1ejNhaG8zSWRDRmF1dTh1RUtjSitkY3FiOHNDRE5mYWZvdTJWZTF6dWFXMklTUXRlVG5WSEV1RVVMb2hPZU54ME5MaWZva050YmdiS2lVQnQ4bVQydm9DUXM4UnYwWHJIaXo5dXluTFBZVzQ4VmlyQkRsb0ZEcVBWdHUwWDZiZnB5VTFsQTdneStVTFJXN3V1STU1dm9kMWF5RGtUSkZGbzBtNXdtMm1pTjlYa0c0ZktacmpqY29rd0ZFc0k1eENNbUZndVhPeUg3by84Vk1wNVpFZnpMdzJYR29EbEYwKzdReG1CdzhBS1owUFlQaTJTb0t6SERRb0kyRE9ya2lEd2YwSEN4ZHlFZFpObDFaNVAwQWpKNEcreVlrRkw4NnB6R2Y4RXk5aFU0YkorNmJza1lVcjVSNkZENEN2ejl6SWxRVVliQW9sMkQ1VXptWmpLeDU0MmFtQk5iSG9UenZ5K0ZvR2MyNENPeEhPejFHODFRenYwWkhWUy96QTk5RUw0OTQvR2pCbFUwNDI5VDFVQVAwZjYzTlVESlRnNGF2YzBYVDdNWHNmTXkyUlYxUko1S25ROVNCeEY4SGNHZ05DTmRTRHhsSUU2MGRQR2pSdURQQ2ErRGxjd2Zpb04xQjkvUEdDdUNKU0FNc0hnRGlQUktzRHBVT0J0RFBlMitiWFFpZjMzUVZ2MVMva3cvVG9IVUZvYmZRRVJJUUJWWW5MWmZLellkRWd0aXIzaVRINTBrak9yUDVlWHRWY1FPdjVZMzJLNGFWNzdhVHN4dWUxRWFDZWQ5MW13T29IZVhXdDFVblVJc3c1LzdHWFFkNFZPVXpxWHhZRS9FeE9sUE9xWDh2N1pZSjFtaDBnTGRLMDVJRmJCUEVqcmEyN3lidENWMThJemNKc0xJcDZoVVhvREt6RmkzNUpHZW1RL2tjd0plOVZmZjVJdzhrQ0VvbW9KTnNURzFGVVNHUnM4TDZNWHhNRFpkQXQ4UFFXbDczc1lIZ3dROWc4WitBb0JTUjR5citaUjVPNUhkN3hKZEhXcTJXRVVkM2Y0SEdoRWNyZmVheHdmQVFTT0hoZmk4YVZ1S0pvNndoS1ZLYVVocnBlOGFGR3FCVmVrNlJTWUF3dXRjRjJ3YkUzd1YzWmZZclcvUmRjdmFjZUtlSUd6T2ZBSWhoTFFkZVA5NGJrczBjeUxjekFMcS8vQXhzdTkyaml0WUxBSlA4RGdLdWxjWnp4L0RRQjAwenlDTEFXaVczQVA5akI4RzROeVVXLy9oNnZaZ1VyVEZ5T0hmK3F0NFFLK0lRcTU3YVlWRVBrRzRsZEdZOFg3cFJ5bm05NWJhWWtFL2syK21PVUY5RFBERmMraGpvcjc1UWhZYlcxdUJUbUc2eVhZdFJmOWt0R1crY0hIQzFidWtyWHV2enNPVUl2T1JrbEtybENHZ09Tek9KZ291MmpsL0Z4bkNobHVmdFAwaUYxNmk5dEtVdVhma0krSHZZcjE5NGEzY3UySVVFVmFJUGJXSFRCWEEwcXFsM3d1OC8xdzhJaGgrUGlFKzltNUFTa1A4cGE3cVJzZjRVbHRoVXpBZXFreHl1UXMwcVo4ZTArdVZJczh3SDFNTW5aV1YvT1Z1aVN0b3V4c0YzZ3ZZRG1SMThyUVorUk9VVEdOTFdNOFd3ajdtSDY5K1NocXI5OXRpSjdSakp0aXBHd0FOS1p1N2RRdU1iREVpNTZNSWRDQlE4ejhWRG40U0FuVUdLS0pSSitzRnJ4bVp5ODZZUHhCOTZpOVVMRmJwNVN2MnF3T0xHS3BETEVEeDdFM3BWUndkU0cvaUtYcnI3ZDJxMjA2VVg2TlZsazJFR1NlbUFsdkJPY3YzNW9Jd3lWc2FkT2ZLWWhpdEQ0RnRmaWg0dk54N0djWXFYSm9LVEVHMHdaUUVFdjdjNk9DQ2U1TDgzTVlpclduL05xWWNUeW5YY0tkeHlCM2VJdURCVkVMZWozYzRhcDdHcisrNmRZbmwyQmo4eXVnM0ZaQms3L1BaTzViYWsrN2FmRDFPckkzZ1ZKNWh1ajUrcnZOODFrY0kvOFJEUkUwS1UwQU8vaVZzRTh4dTR5QzBqcE1pS0J5TTZlYUhEdUx6dkh3elNCVW1ZMlF4WVdUNW40aUszbklOcHhnYkxxeStlUzRJYnQyQWJ1YzZPS0gxbGYwbXpDOHdtemI4OFg2ZG5hZ1BLNDl6T0RtYnlFalN4aUJLQnhQQkxLaisyYlpYc1IzVmlsQnZHSnpsU1ZwRUNpQUdDc2tHU0xzdFE5Y1J0a2FxNFhZUGkzNUJWSEIzQzBudG1UTkMydi9GUW40UlJNYitGbEJsTExJT2x2NnFOYjR4bXcvNzJwWlRkVHJYOS9TRW5jdnJWeUNYeFRvbTAvdXNrU3pCK3dpY1JMWXE3cVpseGNtT2hwbno0cXk1b1hRQVFEVFp1R3IrZ3VSQkp2dWNNeFNZYVI3Sk0wTEJUNTlTQnNicXNRU1hSQUVWYVFXNUFETzVKOGtkVGhKUThIYzMyYkFMSkNRMHByeHB4dk5JdUsrTDZ2cGJuS2dJOXg3R3luTXFRYVU1Nm50YWJvUm8zTEQzR0ZvZmdmYlptcktFQ0NiZGR1QWRXdElDVFlZUFNSOGpLditQOHdBYndJZFh0ZlJjL21oRUV2NVFoRktHaHdFMnkyQ2ZEcGFQdm5SVHIraDE2dXNQMUllbE40dDNOTW1YV2x4TVNtNy9PbGRDM0FTVnpYT01kKzJ6Yzg0aWk3dGVzK0xYZ1ZlTVMxSnJWbEhTeG1WUW9pTlkrUmJzeVhZOUlSbVhUMmdOM1JkRWNhS25LNStLWFQ1WUpCNHZZTzhYM1pDREE9PSIsIm1hYyI6ImEwNmM3ZjYxNzEwYmFmNmYwNDQ0ZTI4MTYzYWU5ZTkzYWZhMDczNDY1YWFiODRkMzVjZTUyOTQ4NDQ5NzIyN2MiLCJ0YWciOiIifQ==";

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (decrypt($this->offices) as $row) {
            Office::create($row);
        }
    }
}
